<?php

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Kamma\Bill;
use App\Models\Kamma\KsiReport;
use App\Models\Kamma\Memo;
use App\Models\Tracking\FileTracker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * System Admin, copied from KADCA's (itself copied in layout and wording from KLAES: module dashboard, Configurable Entries >
 * Entries & Rates, User Roles, Digital Signature Control, System Settings, Folder Watcher, Bulk SMS Management, Verify QRCode,
 * KLAES Mobile Apps, KLAES Documentation). Reads are real (KAMMA's own tables, config, the KAMMA\DOC folder, KLAES users
 * read-only); every save posts to the preview stub. Roles and permissions stay on hold (user 2026-11-22).
 */
class AdminController extends Controller
{
    /** Serial generators KAMMA uses (serials name => what it numbers and its format). */
    public const SERIALS = [
        'kamma-bill' => ['KAMMA bill (processing fee, betterment)', 'BILL-KMA-YYYY-00001'],
        'file_request' => ['File request (Quick Search)', 'FR-KMA-YYYY-00001'],
    ];

    public function dashboard(): View
    {
        $count = fn (callable $q) => $this->safe($q, null);

        return view('ui.admin.dashboard', [
            'figures' => [
                'applications' => $count(fn () => KsiReport::query()->count()),
                'bills' => $count(fn () => Bill::query()->count()),
                'trackers' => $count(fn () => FileTracker::query()->count()),
                'shadow' => $count(fn () => FileIndexing::query()->count()),
                'users' => $count(fn () => User::query()->where('is_active', 1)->count()),
            ],
            'env' => app()->environment(),
            'database' => config('database.connections.'.config('database.default').'.database'),
        ]);
    }

    public function entities(): View
    {
        $serials = $this->safe(fn () => DB::table('serials')->orderBy('name')->orderByDesc('year')->get(), collect());

        return view('ui.admin.entities', [
            'serials' => $serials,
            'fee' => (int) config('kamma.bills.processing_fee'),
            'purposes' => config('kamma.request_purposes', []),
            'pageTypes' => config('kamma.dms_page_types', []),
        ]);
    }

    public function roles(Request $request): View
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 60);
        $users = $this->safe(fn () => User::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(20)->withQueryString(), null);

        return view('ui.admin.roles', [
            'modules' => collect(config('kamma_menu', []))->pluck('label')->all(),
            'users' => $users,
            'q' => $q,
        ]);
    }

    public function signatures(): View
    {
        return view('ui.admin.signatures');
    }

    public function settings(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['general', 'company', 'email', 'security'], true) ? $request->query('tab') : 'general';

        return view('ui.admin.settings', ['tab' => $tab]);
    }

    public function folderWatcher(): View
    {
        return view('ui.admin.folder-watcher', ['disk' => config('kamma.dms_disk'), 'uploads' => config('kamma.uploads_disk')]);
    }

    public function sms(): View
    {
        // KAMMA holds no applicant phone yet (portal accounts come with the backend phase).
        return view('ui.admin.sms', ['applicants' => 0]);
    }

    /** Verify QRCode: KAMMA's own documents carry their number in the QR (bill no., memo no., tracking ID, file no.). */
    public function verify(Request $request): View
    {
        $ref = mb_substr(trim((string) $request->query('ref', '')), 0, 100);
        $result = null;
        if ($ref !== '') {
            $result = $this->safe(fn () => $this->lookup($ref), ['kind' => 'error']);
        }

        return view('ui.admin.verify', ['ref' => $ref, 'result' => $result, 'examples' => ['BILL-KMA-2026-00001', 'memo no.', 'KMA-TRK-…']]);
    }

    public function mobileApps(): View
    {
        return view('ui.admin.mobile-apps');
    }

    public function documentation(Request $request): View
    {
        $docs = $this->docs();
        $current = $docs->firstWhere('name', (string) $request->query('doc'));

        return view('ui.admin.documentation', ['docs' => $docs, 'current' => $current, 'q' => mb_substr((string) $request->query('q', ''), 0, 60)]);
    }

    /** Serves one file of KAMMA\DOC by its exact listed name (never a path), inline for PDFs and images. */
    public function documentationFile(Request $request): BinaryFileResponse
    {
        $doc = $this->docs()->firstWhere('name', (string) $request->query('name'));
        abort_unless($doc, 404);

        return $request->boolean('download') ? response()->download($doc['path'], $doc['name']) : response()->file($doc['path']);
    }

    private function docs()
    {
        $dir = base_path('DOC');
        if (! is_dir($dir)) {
            return collect();
        }

        return collect(scandir($dir))
            ->reject(fn ($f) => str_starts_with($f, '.') || is_dir($dir.DIRECTORY_SEPARATOR.$f))
            ->map(fn ($f) => [
                'name' => $f,
                'path' => $dir.DIRECTORY_SEPARATOR.$f,
                'ext' => Str::lower(pathinfo($f, PATHINFO_EXTENSION)),
                'size' => filesize($dir.DIRECTORY_SEPARATOR.$f),
                'modified' => filemtime($dir.DIRECTORY_SEPARATOR.$f),
            ])->values();
    }

    /** @return array{kind:string, title?:string, rows?:array<string,mixed>, link?:?string} */
    private function lookup(string $ref): array
    {
        $money = fn ($v) => $v === null ? null : '₦'.number_format((float) $v, 2);

        if ($bill = Bill::query()->where('bill_no', $ref)->first()) {
            return ['kind' => 'found', 'title' => 'Bill '.$bill->bill_no, 'rows' => [
                'Bill type' => $bill->bill_type, 'Amount' => $money($bill->amount), 'Status' => $bill->status,
                'File Number' => $bill->file_number, 'Payer' => $bill->payer_name, 'Receipt No.' => $bill->receipt_no,
            ], 'link' => route('kamma.bills.print', $bill)];
        }
        if ($memo = Memo::query()->where('memo_no', $ref)->first()) {
            return ['kind' => 'found', 'title' => 'Memo '.$memo->memo_no, 'rows' => [
                'Memo No.' => $memo->memo_no, 'File Number' => $memo->file_number, 'Applicant' => $memo->applicant_name,
                'Location' => $memo->property_location, 'Status' => $memo->status,
            ], 'link' => null];
        }
        if ($tracker = FileTracker::query()->with('currentUnit')->where('tracking_id', $ref)->first()) {
            return ['kind' => 'found', 'title' => 'File tracker '.$tracker->tracking_id, 'rows' => [
                'Tracking ID' => $tracker->tracking_id, 'Now at' => $tracker->currentUnit?->name,
            ], 'link' => route('tracking.show', $tracker)];
        }
        if ($file = FileIndexing::query()->where('file_number', $ref)->first()) {
            return ['kind' => 'found', 'title' => 'Shadow file '.$file->file_number, 'rows' => [
                'File Number' => $file->file_number, 'File Title' => $file->file_title, 'Plot No.' => $file->plot_number,
                'District' => $file->district, 'LGA' => $file->lga, 'Location' => $file->location,
            ], 'link' => route('kamma.shadow-files.show', $file)];
        }

        return ['kind' => 'none'];
    }

    private function safe(callable $fn, mixed $fallback): mixed
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            report($e);

            return $fallback;
        }
    }
}
