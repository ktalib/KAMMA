<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Tracking\FileMovement;
use App\Models\Tracking\FileTracker;
use App\Models\Tracking\Unit;
use App\Services\Tracking\FileTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * File Movement (Department), copied from KLAES (secretariat-file-log): My Office, the five counters, scan to
 * receive / send, batch mode, log manually, and the Files / Mails tables (Incoming / Outgoing, Yet to Receive /
 * Received). My Office is remembered for the session, as KLAES keeps the officer's office.
 */
class DepartmentMovementController extends Controller
{
    public function index(Request $request)
    {
        $units = Unit::active()->get(['id', 'name']);
        if ($request->filled('unit') && $units->firstWhere('id', (int) $request->query('unit'))) {
            session(['kamma_office' => (int) $request->query('unit')]);
        }
        $office = $units->firstWhere('id', (int) session('kamma_office')) ?? $units->first();

        return view('tracking.movement', ['units' => $units, 'office' => $office]);
    }

    public function data(Request $request): JsonResponse
    {
        $office = (int) $request->query('office');
        $kind = $request->query('kind') === 'mails' ? 'mail' : 'file';
        $dir = $request->query('dir') === 'outgoing' ? 'outgoing' : 'incoming';
        $done = $request->query('state') === 'done';
        $q = trim((string) $request->query('search', ''));
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));

        $moves = FileMovement::query()->with(['tracker.currentUnit', 'tracker.toUnit', 'fromUnit', 'toUnit'])
            ->whereHas('tracker', fn ($t) => $t->where('kind', $kind)->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('reference', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%")->orWhere('tracking_id', 'like', "%{$q}%"))))
            ->when($from, fn ($w) => $w->where('acted_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($w) => $w->where('acted_at', '<=', $to->copy()->endOfDay()));

        if ($dir === 'incoming') {
            $moves->where('to_unit_id', $office)->where('action', $done ? 'received' : 'sent');
            if (! $done) {
                $moves->whereHas('tracker', fn ($t) => $t->where('status', 'in_transit')->where('to_unit_id', $office));
            }
        } else {
            $moves->where('from_unit_id', $office)->where('action', 'sent')
                ->whereHas('tracker', fn ($t) => $done ? $t->where(fn ($w) => $w->where('status', 'in_unit')->orWhere('to_unit_id', '!=', $office)) : $t->where('status', 'in_transit'));
        }

        $rows = $moves->orderByDesc('acted_at')->orderByDesc('id')->limit(300)->get()
            ->unique(fn ($m) => $m->file_tracker_id.($done ? '-'.$m->id : ''))
            ->map(fn ($m) => [
                'tracking_id' => $m->tracker->tracking_id,
                'url' => route('tracking.show', $m->tracker),
                'receive_url' => route('tracking.receive', $m->tracker),
                'file_number' => $m->tracker->reference,
                'file_title' => $m->tracker->title,
                'from' => $m->fromUnit?->name ?? '—',
                'to' => $m->toUnit?->name,
                'date' => $m->acted_at?->format('d M Y, g:i a'),
                'purpose' => $m->purpose,
                'notes' => $m->notes,
                'timeline' => $this->timeline($m->tracker->due_on),
                'due' => $m->tracker->due_on?->format('d M Y'),
                'can_receive' => $dir === 'incoming' && ! $done,
            ])->values();

        return response()->json(['rows' => $rows, 'counts' => $this->counts($office)]);
    }

    /** Scan (or type) a tracking ID or file number: what the item is and where it is. */
    public function scan(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $t = FileTracker::with(['currentUnit', 'toUnit'])->where('tracking_id', mb_strtoupper($q))->orWhere('reference', $q)->first();
        if (! $t) {
            $file = FileIndexing::query()->live()->where('file_number', $q)->first();
            $t = $file ? FileTracker::with(['currentUnit', 'toUnit'])->where('file_indexing_id', $file->id)->first() : null;
        }
        if (! $t) {
            return response()->json(['found' => false, 'message' => "Nothing tracked matches \"{$q}\". Log it manually first."]);
        }

        return response()->json(['found' => true, 'tracking_id' => $t->tracking_id, 'file_number' => $t->reference, 'file_title' => $t->title,
            'kind' => $t->kind, 'status' => $t->status, 'unit_id' => (int) $t->current_unit_id, 'unit' => $t->currentUnit?->name,
            'to_unit_id' => $t->to_unit_id ? (int) $t->to_unit_id : null, 'to_unit' => $t->toUnit?->name,
            'receive_url' => route('tracking.receive', $t), 'send_url' => route('tracking.send', $t)]);
    }

    /** Log manually: a mail (reference, subject, from) or a KAMMA file into My Office. */
    public function log(Request $request, FileTracking $tracking): JsonResponse
    {
        $data = $request->validate([
            'office' => ['required', 'integer'],
            'kind' => ['required', 'in:file,mail'],
            'file_indexing_id' => ['required_if:kind,file', 'nullable', 'integer'],
            'reference' => ['required_if:kind,mail', 'nullable', 'string', 'max:150'],
            'title' => ['required_if:kind,mail', 'nullable', 'string', 'max:500'],
            'mail_from' => ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        foreach (['reference', 'title', 'mail_from'] as $f) {
            if (isset($data[$f])) {
                $data[$f] = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $data[$f])));
            }
        }
        $t = $tracking->log($data + ['unit_id' => $data['office'], 'priority' => 'Medium', 'purpose' => 'Logged manually'], $request->user());

        return response()->json(['ok' => true, 'tracking_id' => $t->tracking_id, 'label_url' => route('tracking.label', $t)]);
    }

    private function counts(int $office): array
    {
        $split = fn ($q) => ['files' => (clone $q)->where('kind', 'file')->count(), 'mails' => (clone $q)->where('kind', 'mail')->count()];
        $pending = FileTracker::query()->where('status', 'in_transit')->where('to_unit_id', $office);
        $held = FileTracker::query()->where('status', 'in_unit')->where('current_unit_id', $office);
        $todayIds = FileMovement::where('action', 'received')->where('to_unit_id', $office)->where('acted_at', '>=', now()->startOfDay())->pluck('file_tracker_id');
        $sentIds = FileMovement::where('action', 'sent')->where('from_unit_id', $office)->pluck('file_tracker_id');
        $weekIds = FileMovement::where('action', 'sent')->where('from_unit_id', $office)->where('acted_at', '>=', now()->subDays(7))->pluck('file_tracker_id');

        $out = [
            'pending' => $split($pending),
            'held' => $split($held),
            'today' => $split((clone $held)->whereIn('id', $todayIds)),
            'transit' => $split(FileTracker::query()->where('status', 'in_transit')->whereIn('id', $sentIds)->where('to_unit_id', '!=', $office)),
            'outgoing' => $split(FileTracker::query()->whereIn('id', $weekIds)),
        ];
        foreach ($out as $k => $v) {
            $out[$k]['total'] = $v['files'] + $v['mails'];
        }

        return $out;
    }

    private function timeline($due): ?string
    {
        if (! $due) {
            return null;
        }
        $days = now()->startOfDay()->diffInDays($due, false);

        return $days < 0 ? 'red' : ($days <= 1 ? 'amber' : 'green');
    }

    private function date($v): ?Carbon
    {
        try {
            return filled($v) ? Carbon::createFromFormat('Y-m-d', $v) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
