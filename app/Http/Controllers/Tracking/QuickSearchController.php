<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\Tracking\FileRequest;
use App\Models\Tracking\Unit;
use App\Services\Tracking\QuickSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Quick Search & File Location, copied from KLAES (create-file-tracker.quick-search and its JSON endpoints:
 * resolve, file request, file request log, feedback, report).
 */
class QuickSearchController extends Controller
{
    public function index()
    {
        return view('tracking.quick-search', [
            'units' => Unit::active()->get(['id', 'name']),
            'purposes' => array_keys(config('kamma.request_purposes')),
        ]);
    }

    public function resolve(Request $request, QuickSearch $search): JsonResponse
    {
        $q = trim((string) $request->query('query', ''));

        return $q === '' ? response()->json(['message' => 'Choose a file number first.'], 422) : response()->json($search->resolve($q));
    }

    public function store(Request $request, QuickSearch $search): JsonResponse
    {
        $data = $request->validate([
            'file_number' => ['required', 'string', 'max:150'],
            'requester_unit_id' => ['required', 'integer'],
            'requester_name' => ['required', 'string', 'max:300'],
            'purpose' => ['required', 'string', 'max:300'],
            'needed_by' => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $data['requester_name'] = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $data['requester_name'])));
        $fr = $search->request($data, $request->user());

        return response()->json(['ok' => true, 'request_no' => $fr->request_no, 'result' => $search->resolve($data['file_number'])]);
    }

    public function respond(Request $request, FileRequest $fileRequest, QuickSearch $search): JsonResponse
    {
        $data = $request->validate(['outcome' => ['required', 'in:found,pending,nwk'], 'note' => ['nullable', 'string', 'max:2000']]);
        $search->respond($fileRequest, $data['outcome'], $data['note'] ?? null, $request->user());

        return response()->json(['ok' => true]);
    }

    /** File Search History: every request, newest first, with a status filter and search. */
    public function log(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', '');
        $rows = $this->filtered($request)
            ->when(in_array($status, ['awaiting', 'found', 'not_found'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->limit(200)->get();
        $counts = FileRequest::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return response()->json([
            'total' => (int) $counts->sum(),
            'counts' => ['awaiting' => (int) ($counts['awaiting'] ?? 0), 'found' => (int) ($counts['found'] ?? 0), 'not_found' => (int) ($counts['not_found'] ?? 0)],
            'rows' => $rows->map(fn ($r) => $this->row($r))->values(),
        ]);
    }

    /** Registry Monitor Feedback: the answered requests (KLAES "SCB Feedback", now Registry Monitor). */
    public function feedback(Request $request): JsonResponse
    {
        return response()->json(['rows' => $this->filtered($request)->where('status', '!=', 'awaiting')
            ->orderByDesc('responded_at')->limit(200)->get()->map(fn ($r) => $this->row($r))->values()]);
    }

    public function report(Request $request, QuickSearch $search): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return response()->json(['report' => $search->report($from, $to), 'date' => now()->format('D, j M Y')]);
    }

    public function csv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $rows = FileRequest::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->copy()->endOfDay()))
            ->orderBy('id');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['S/N', 'Request No.', 'Requested', 'File No.', 'File title', 'Blind', 'Requester unit', 'Requester', 'Purpose',
                'Needed by', 'Status', 'Responded', 'Responded by', 'Note']);
            $n = 0;
            $rows->chunkById(500, function ($chunk) use ($out, &$n) {
                foreach ($chunk as $r) {
                    $row = $this->row($r);
                    fputcsv($out, [++$n, $r->request_no, $r->created_at?->format('Y-m-d H:i'), $r->file_number, $r->file_title, $r->is_blind ? 'Yes' : 'No',
                        $r->requester_unit_name, $r->requester_name, $r->purpose, $r->needed_by?->format('Y-m-d'), $row['status_label'],
                        $r->responded_at?->format('Y-m-d H:i'), $r->responded_by_name, $r->response_note]);
                }
            });
            fclose($out);
        }, 'kamma-file-search-report-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        $s = trim((string) $request->query('search', ''));

        return FileRequest::query()->when($s !== '', fn ($q) => $q->where(fn ($w) => $w->where('file_number', 'like', "%{$s}%")
            ->orWhere('file_title', 'like', "%{$s}%")->orWhere('requester_name', 'like', "%{$s}%")->orWhere('request_no', 'like', "%{$s}%")));
    }

    private function row(FileRequest $r): array
    {
        $label = match (true) {
            $r->status === 'awaiting' => 'Awaiting',
            $r->status === 'found' => 'Found',
            $r->not_found_type === 'nwk' => 'Not Found (NWK)',
            default => 'Not Found (Pending)',
        };

        return [
            'id' => $r->id,
            'request_no' => $r->request_no,
            'file_number' => $r->file_number,
            'file_title' => $r->file_title,
            'is_blind' => $r->is_blind,
            'requester' => $r->requester_name,
            'requester_unit' => $r->requester_unit_name,
            'purpose' => $r->purpose,
            'needed_by' => $r->needed_by?->format('d M Y'),
            'requested_at' => $r->created_at?->format('d M Y, g:i a'),
            'status' => $r->status,
            'not_found_type' => $r->not_found_type,
            'status_label' => $label,
            'responded_at' => $r->responded_at?->format('d M Y, g:i a'),
            'responder' => $r->responded_by_name,
            'note' => $r->response_note,
            'respond_url' => route('tracking.quick-search.respond', $r),
        ];
    }

    private function range(Request $request): array
    {
        $d = function ($v) {
            try {
                return filled($v) ? Carbon::createFromFormat('Y-m-d', $v) : null;
            } catch (\Throwable) {
                return null;
            }
        };

        return [$d($request->query('from')), $d($request->query('to'))];
    }
}
