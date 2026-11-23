<?php

namespace App\Services\Tracking;

use App\Models\Dms\FileIndexing;
use App\Models\Tracking\FileRequest;
use App\Models\Tracking\FileTracker;
use App\Models\Tracking\Unit;
use App\Models\User;
use App\Services\Serials;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * KAMMA Quick Search & File Location, copied from KLAES (CreateFileTrackerController quick-search): resolve a file
 * number to a status and a next action, send a file search request, and let the Registry Monitor answer it.
 * The statuses keep KLAES's codes (IN_TRANSIT, IN_ARCHIVE, …) so the copied screen reads the same.
 */
class QuickSearch
{
    public function __construct(private Serials $serials)
    {
    }

    /** @return array<string, mixed> the payload the copied result card renders */
    public function resolve(string $query): array
    {
        $q = trim($query);
        // A KAMMA file keeps its KLAES land-file number; the KMA-TRK tracking ID is KAMMA's own marker.
        $file = FileIndexing::query()->live()->where('file_number', $q)->first();
        $tracker = $file
            ? FileTracker::with(['currentUnit', 'toUnit'])->where('file_indexing_id', $file->id)->first()
            : FileTracker::with(['currentUnit', 'toUnit'])->where('tracking_id', mb_strtoupper($q))->orWhere('reference', $q)->first();
        $file ??= $tracker?->shadowFile;

        if (! $file && ! $tracker) {
            return ['found' => false, 'file_number' => $q, 'status' => 'FILE_NOT_FOUND', 'file_title' => null,
                'next_action' => 'No KAMMA file has this number. Commission the shadow file first (Shadow File Commissioning), or check the number.', 'can_send_fr' => false];
        }

        $latest = FileRequest::query()
            ->where(fn ($w) => $w->when($file, fn ($x) => $x->where('file_indexing_id', $file->id))
                ->when($tracker, fn ($x) => $x->orWhere('file_tracker_id', $tracker->id)))
            ->orderByDesc('id')->first();

        $status = match (true) {
            $tracker === null => 'PENDING_FILE',
            $tracker->status === 'in_transit' => 'IN_TRANSIT',
            $tracker->currentUnit?->name === 'Archive' => 'IN_ARCHIVE',
            default => 'IN_UNIT',
        };
        // The Registry Monitor's answer to the latest request refines a file at rest (KLAES: IN_ARCHIVE_FOUND …).
        if ($latest && $status !== 'IN_TRANSIT' && $latest->status !== 'awaiting') {
            $base = $status === 'PENDING_FILE' ? 'IN_UNIT' : $status;
            $status = $latest->status === 'found' ? $base.'_FOUND'
                : ($latest->not_found_type === 'nwk' ? 'MISSING_FILE' : $base.'_NOT_FOUND');
        }

        $awaiting = $latest && $latest->status === 'awaiting';
        $lastMove = $tracker?->movements()->orderByDesc('acted_at')->orderByDesc('id')->first();

        return [
            'found' => true,
            'file_number' => $file?->file_number ?? $tracker->reference,
            'file_title' => $file?->file_title ?? $tracker?->title,
            'application_no' => null,
            'application_url' => null,
            'land_file' => $file?->file_number,
            'plot_no' => $file?->plot_number,
            'district' => $file?->district,
            'lga' => $file?->lga,
            'location' => $file?->location,
            'land_use' => $file?->land_use_type,
            'proposed_use' => null,
            'desk' => null,
            'tracking_id' => $tracker?->tracking_id,
            'tracker_url' => $tracker ? route('tracking.show', $tracker) : null,
            'label_url' => $tracker ? route('tracking.label', $tracker) : null,
            'kind' => $tracker?->kind ?? 'file',
            'status' => $status,
            'unit_name' => $tracker?->currentUnit?->name,
            'current_location' => $tracker ? ($tracker->status === 'in_transit' ? $tracker->toUnit?->name : $tracker->currentUnit?->name) : null,
            'shelf' => $tracker?->shelf_location,
            'receiving_department' => $tracker?->status === 'in_transit' ? $tracker->toUnit?->name : null,
            'logged_out_at' => $lastMove && $lastMove->action === 'sent' ? $lastMove->acted_at?->format('d M Y, g:i a') : null,
            'duration_with_holder' => $lastMove ? $lastMove->acted_at?->diffForHumans(null, true) : null,
            'next_action' => $this->nextAction($status, $tracker, $awaiting),
            'can_send_fr' => ! $awaiting && $status !== 'IN_TRANSIT',
            'fr_sent_at' => $awaiting ? $latest->created_at?->format('d M Y, g:i a') : null,
            'fr_request_no' => $awaiting ? $latest->request_no : null,
            'fr_found' => $latest && $latest->status === 'found' ? $this->answer($latest) : null,
            'fr_not_found' => $latest && $latest->status === 'not_found' ? $this->answer($latest) + ['not_found_type' => $latest->not_found_type === 'nwk' ? 'MISSING' : 'PENDING'] : null,
            'movements' => $tracker ? $tracker->movements()->with(['fromUnit', 'toUnit'])->orderBy('acted_at')->orderBy('id')->get()->map(fn ($m) => [
                'action' => $m->action,
                'from' => $m->fromUnit?->name,
                'to' => $m->toUnit?->name,
                'purpose' => $m->purpose,
                'notes' => $m->notes,
                'by' => $m->acted_by_name,
                'at' => $m->acted_at?->format('d M Y, g:i a'),
            ])->all() : [],
        ];
    }

    /** Send a file search request to the Registry Monitor. */
    public function request(array $data, User $officer): FileRequest
    {
        $found = $this->resolve($data['file_number']);
        if (! $found['found']) {
            throw ValidationException::withMessages(['file_number' => 'No KAMMA file has this number.']);
        }
        if (! $found['can_send_fr']) {
            throw ValidationException::withMessages(['file_number' => $found['status'] === 'IN_TRANSIT'
                ? 'The file is in transit; wait for it to be received.'
                : "A request for {$found['file_number']} is already awaiting the Registry Monitor ({$found['fr_request_no']})."]);
        }
        $unit = Unit::where('id', $data['requester_unit_id'])->where('is_active', true)->first();
        if (! $unit) {
            throw ValidationException::withMessages(['requester_unit_id' => 'Choose the requesting unit.']);
        }

        return DB::transaction(function () use ($data, $officer, $found, $unit) {
            $tracker = $found['tracking_id'] ? FileTracker::where('tracking_id', $found['tracking_id'])->first() : null;
            $year = (int) now()->format('Y');

            return FileRequest::create([
                'request_no' => sprintf('FR-KMA-%d-%05d', $year, $this->serials->next('file_request', $year)),
                'file_tracker_id' => $tracker?->id,
                'file_indexing_id' => $tracker?->file_indexing_id ?? FileIndexing::query()->live()->where('file_number', $found['file_number'])->value('id'),
                'file_number' => $found['file_number'],
                'file_title' => $found['file_title'],
                'status_at_request' => $found['status'],
                'is_blind' => $tracker === null,
                'requester_unit_id' => $unit->id,
                'requester_unit_name' => $unit->name,
                'requester_name' => $data['requester_name'],
                'purpose' => $data['purpose'],
                'needed_by' => $data['needed_by'] ?? null,
                'status' => 'awaiting',
                'created_by' => $officer->getKey(),
                'created_by_name' => $officer->name,
            ]);
        });
    }

    /** The Registry Monitor's answer: Found, or Not Found (Pending / NWK). */
    public function respond(FileRequest $request, string $outcome, ?string $note, User $officer): FileRequest
    {
        if ($request->status !== 'awaiting') {
            throw ValidationException::withMessages(['request' => "{$request->request_no} was already answered."]);
        }
        $request->update([
            'status' => $outcome === 'found' ? 'found' : 'not_found',
            'not_found_type' => $outcome === 'found' ? null : ($outcome === 'nwk' ? 'nwk' : 'pending'),
            'response_note' => filled($note) ? $note : null,
            'responded_by_name' => $officer->name,
            'responded_at' => now(),
        ]);

        return $request;
    }

    /** File Search Report cards (KLAES qs-rep-*), for a date range. */
    public function report(?Carbon $from, ?Carbon $to): array
    {
        $base = FileRequest::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->copy()->endOfDay()));
        $today = FileRequest::query()->where('created_at', '>=', now()->startOfDay());

        return [
            'submitted_today' => (clone $today)->count(),
            'today_found' => (clone $today)->where('status', 'found')->count(),
            'today_not_found' => (clone $today)->where('status', 'not_found')->count(),
            'today_awaiting' => (clone $today)->where('status', 'awaiting')->count(),
            'blind' => (clone $base)->where('is_blind', true)->count(),
            'found' => (clone $base)->where('status', 'found')->count(),
            'not_found' => (clone $base)->where('status', 'not_found')->where('not_found_type', 'pending')->count(),
            'missing' => (clone $base)->where('status', 'not_found')->where('not_found_type', 'nwk')->count(),
            'awaiting' => (clone $base)->where('status', 'awaiting')->count(),
        ];
    }

    private function nextAction(string $status, ?FileTracker $tracker, bool $awaiting): string
    {
        if ($awaiting) {
            return 'Request sent; awaiting the Registry Monitor.';
        }

        return match (true) {
            $status === 'IN_TRANSIT' => 'The file is on its way to '.$tracker?->toUnit?->name.'; it must be received there before it can be requested.',
            $status === 'PENDING_FILE' => 'The file is not tracked yet. Log the file, or send a blind request to the Registry Monitor.',
            str_ends_with($status, '_FOUND') && ! str_contains($status, 'NOT') => 'The file was found. Collect it from '.($tracker?->currentUnit?->name ?? 'the holding unit').'.',
            $status === 'MISSING_FILE' => 'Reported Not With KAMMA (NWK). Send a new request if it should be searched again.',
            str_contains($status, 'NOT_FOUND') => 'Reported Not Found (pending). Send a new request if it should be searched again.',
            default => 'Send File Search Request to the Registry Monitor.',
        };
    }

    private function answer(FileRequest $r): array
    {
        return [
            'request_no' => $r->request_no,
            'responded_at' => $r->responded_at?->format('d M Y, g:i a'),
            'responder' => $r->responded_by_name,
            'requested_at' => $r->created_at?->format('d M Y, g:i a'),
            'receiving_officer' => $r->requester_name,
            'requester_office' => $r->requester_unit_name,
            'note' => $r->response_note,
        ];
    }
}
