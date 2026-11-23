<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\Tracking\FileMovement;
use App\Models\Tracking\FileTracker;
use App\Models\Tracking\Unit;
use App\Models\User;
use App\Services\Tracking\FileTracking;
use App\Services\Tracking\QuickSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Log a File (Create File Tracker), copied from KLAES create_file_tracker_page/index: the Create File Tracker
 * tab (File Details, Office Details, File Log Session, Current Date & Time, Quick Actions) and the File Tracker
 * Log tab (My Files, Awaiting Acceptance, Other Handler's Files, Completed; Control Panel; File Log Table).
 */
class LogFileController extends Controller
{
    public function create()
    {
        return view('tracking.log-a-file', [
            'units' => Unit::active()->get(['id', 'name']),
            'purposes' => config('kamma.request_purposes'),
            'priorities' => FileTracking::PRIORITIES,
        ]);
    }

    /** File details for the loaded file number (KLAES: fetched from File Indexing after Quick Search). */
    public function file(Request $request, QuickSearch $search): JsonResponse
    {
        return response()->json($search->resolve((string) $request->query('file_number', '')));
    }

    /** Officers to receive the file: KLAES accounts (staff sign in with them), active only. */
    public function officers(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $users = User::query()->where('is_active', 1)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%")))
            ->orderBy('first_name')->limit(20)->get(['id', 'first_name', 'last_name', 'username', 'rank']);

        return response()->json(['officers' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'rank' => $u->rank])->values()]);
    }

    public function store(Request $request, FileTracking $tracking)
    {
        $purposes = array_keys(config('kamma.request_purposes'));
        $data = $request->validate([
            'file_number' => ['required', 'string', 'max:150'],
            'priority' => ['required', Rule::in(FileTracking::PRIORITIES)],
            'status_update' => ['required', 'in:log-out,log-in'],
            'purpose' => ['required', 'string', 'max:300'],
            'purpose_other' => ['nullable', 'required_if:purpose,OTHER', 'string', 'max:300'],
            'timeline_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'expected_return_on' => ['nullable', 'date'],
            'num_pages' => ['required', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'origin_unit_id' => ['nullable', 'integer'],
            'to_unit_id' => ['required', 'integer'],
            'holder_user_id' => ['required', 'integer'],
            'log_id' => ['required', 'string', 'max:40', 'regex:/^LOG-\d{14}-\d{3}$/'],
            'override_in_transit' => ['nullable', 'boolean'],
        ], [
            'holder_user_id.required' => 'Select the File Requester (the colleague receiving this file).',
            'to_unit_id.required' => 'Select the Requester Unit.',
        ]);
        if ($data['purpose'] !== 'OTHER' && ! in_array($data['purpose'], $purposes, true)) {
            return back()->withErrors(['purpose' => 'Select the reason this file is being requested.'])->withInput();
        }
        $data['purpose'] = $data['purpose'] === 'OTHER' ? mb_strtoupper(trim($data['purpose_other'])) : $data['purpose'];
        $holder = User::query()->where('is_active', 1)->find($data['holder_user_id']);
        if (! $holder) {
            return back()->withErrors(['holder_user_id' => 'Select the File Requester from the list.'])->withInput();
        }
        $data['holder_name'] = $holder->name;
        $data['override_in_transit'] = $request->boolean('override_in_transit');

        $tracker = $tracking->logFile($data, $request->user());

        return redirect()->route('tracking.create', ['tab' => 'log'])
            ->with('status', "File log saved: {$data['file_number']} ({$tracker->tracking_id}), log {$data['log_id']}.");
    }

    /** File Tracker Log tab: the files with their latest log entry and the handler cards. */
    public function list(Request $request): JsonResponse
    {
        $me = (int) $request->user()->getKey();
        $from = $request->query('from');
        $to = $request->query('to');
        $q = trim((string) $request->query('search', ''));
        $archive = Unit::where('name', 'Archive')->value('id');

        $trackers = FileTracker::query()->with(['currentUnit', 'toUnit', 'shadowFile'])
            ->where('kind', 'file')
            ->when($from, fn ($w) => $w->where('updated_at', '>=', $from))
            ->when($to, fn ($w) => $w->where('updated_at', '<=', $to.' 23:59:59'))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('reference', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%")
                ->orWhere('tracking_id', 'like', "%{$q}%")->orWhere('holder_name', 'like', "%{$q}%")))
            ->orderByDesc('updated_at')->limit(500)->get();
        $logs = FileMovement::query()->whereIn('file_tracker_id', $trackers->pluck('id'))->whereNotNull('log_id')
            ->orderByDesc('acted_at')->orderByDesc('id')->get()->unique('file_tracker_id')->keyBy('file_tracker_id');

        $rows = $trackers->map(function ($t) use ($me, $archive, $logs) {
            $log = $logs->get($t->id);
            $completed = $t->status === 'in_unit' && (int) $t->current_unit_id === (int) $archive;
            $group = match (true) {
                $completed => 'completed',
                (int) $t->holder_user_id === $me && $t->status === 'in_transit' => 'awaiting',
                (int) $t->holder_user_id === $me => 'mine',
                $t->holder_user_id !== null => 'others',
                default => 'unassigned',
            };
            $days = $t->due_on ? now()->startOfDay()->diffInDays($t->due_on, false) : null;

            return [
                'tracking_id' => $t->tracking_id,
                'url' => route('tracking.show', $t),
                'file_number' => $t->reference,
                'file_title' => $t->title,
                'priority' => $t->priority,
                'status' => $t->status === 'in_transit' ? 'In-transit' : ($completed ? 'Completed' : 'Active'),
                'group' => $group,
                'location' => $t->status === 'in_transit' ? 'To '.$t->toUnit?->name : $t->currentUnit?->name,
                'handler' => $t->holder_name,
                'purpose' => $log?->purpose,
                'log_id' => $log?->log_id,
                'logged_out' => $log?->acted_at?->format('d M Y, g:i a'),
                'expected_return' => $t->due_on?->format('d M Y'),
                'pages' => $t->num_pages,
                'timeline' => $days === null ? null : ($days < 0 ? 'red' : ($days <= 1 ? 'amber' : 'green')),
            ];
        })->values();

        return response()->json([
            'counts' => [
                'mine' => $rows->where('group', 'mine')->count(),
                'awaiting' => $rows->where('group', 'awaiting')->count(),
                'others' => $rows->where('group', 'others')->count(),
                'completed' => $rows->where('group', 'completed')->count(),
            ],
            'rows' => $rows,
            'me' => $request->user()->name,
        ]);
    }
}
