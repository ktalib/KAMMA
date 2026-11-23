<?php

namespace App\Http\Controllers\Kamma;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Services\Dms\ShadowFiles;
use App\Services\Kamma\Bills;
use App\Services\Kamma\DeskFlow;
use App\Support\KammaDesk;
use App\Support\KammaKinds;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * KAMMA > Shadow File Commissioning (KAMMA Workflow v1.0 phase 1, new screen in KLAES style): the PS sends the land file
 * from the Cadastral Department for Planning Recommendation; KAMMA picks it from KLAES (selector, never typed), the shadow
 * file is commissioned (KLAES indexing record copied, kamma_matching flagged on KLAES, KMA-TRK tracking starts) and it goes
 * to the MD KAMMA. The file page is the hub for the rest: desks, site visit, KSI, bills, Memo and the return to the PS.
 */
class ShadowFileController extends Controller
{
    public function index(Request $request): View
    {
        $stage = in_array($request->query('stage'), [...array_keys(KammaDesk::FLOW)], true) ? $request->query('stage') : null;
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $files = FileIndexing::query()->live()->intake()->with('tracker')
            ->when($stage, fn ($w) => $w->where('stage', $stage))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('file_number', 'like', "%{$q}%")->orWhere('file_title', 'like', "%{$q}%")
                ->orWhere('plot_number', 'like', "%{$q}%")->orWhere('district', 'like', "%{$q}%")->orWhere('lga', 'like', "%{$q}%")))
            ->latest('received_from_ps_at')->latest('id')->paginate(20)->withQueryString();

        $counts = FileIndexing::query()->live()->intake()->selectRaw('stage, COUNT(*) as n')->groupBy('stage')->pluck('n', 'stage')->all();

        return view('kamma.shadow-files.index', [
            'files' => $files, 'counts' => $counts, 'stage' => $stage, 'q' => $q,
            'kinds' => KammaKinds::KINDS, 'postCofo' => KammaKinds::POST_COFO,
            'today' => FileIndexing::query()->live()->intake()->whereDate('received_from_ps_at', now()->toDateString())->count(),
        ]);
    }

    public function store(Request $request, ShadowFiles $shadow, DeskFlow $flow): RedirectResponse
    {
        $data = $request->validate([
            'klaes_file_indexing_id' => ['required', 'integer'],
            'application_type' => ['required', Rule::in(array_keys(KammaKinds::KINDS))],
            'post_cofo_type' => ['nullable', 'required_if:application_type,master', Rule::in(KammaKinds::POST_COFO)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'klaes_file_indexing_id.required' => 'Select the land file the PS sent.',
            'post_cofo_type.required_if' => 'Choose the post-CofO application (Sub-Division, Merger, Extension or Change of Purpose).',
        ]);

        $remarks = trim(implode(' · ', array_filter([$data['post_cofo_type'] ?? null, $data['remarks'] ?? null])));
        $file = DB::transaction(function () use ($data, $shadow, $flow, $remarks, $request) {
            $file = $shadow->commission((int) $data['klaes_file_indexing_id'], $request->user(), $remarks ?: null);

            return $flow->receiveFromPs($file, $data['application_type'], $request->user());
        });

        return redirect()->route('kamma.shadow-files.show', $file->id)
            ->with('status', "Shadow file commissioned for {$file->file_number} and sent to the MD KAMMA.");
    }

    public function show(int $file, Bills $bills): View
    {
        $f = FileIndexing::query()->live()->with(['tracker', 'deskMovements', 'ksiReports', 'bills', 'photos'])->findOrFail($file);

        return view('kamma.shadow-files.show', [
            'f' => $f,
            'kind' => $f->application_type ? (KammaKinds::KINDS[$f->application_type] ?? null) : null,
            'processingFee' => $bills->processingFee(),
            'next' => $this->nextDesk($f->stage),
            'previous' => $this->previousDesk($f->stage),
        ]);
    }

    public function decide(Request $request, int $file, DeskFlow $flow, Bills $bills): RedirectResponse
    {
        $f = FileIndexing::query()->live()->findOrFail($file);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['recommended', 'not_recommended', 'processing_fee'])],
            'remarks' => ['nullable', 'required_if:decision,not_recommended', 'string', 'max:1000'],
        ], ['remarks.required_if' => 'Give the reason the file is not recommended.']);

        if ($data['decision'] === 'processing_fee') {
            $bill = $bills->raiseProcessingFee($f, $request->user());

            return back()->with('status', "Application Processing Fee bill {$bill->bill_no} raised.");
        }

        $before = KammaDesk::label($f->stage);
        $f = $flow->decide($f, $data['decision'], $data['remarks'] ?? null, $request->user());

        return back()->with('status', ($data['decision'] === 'recommended' ? 'Recommended' : 'Not recommended')." at {$before}: the file is now with ".KammaDesk::label($f->stage).'.');
    }

    /** Phase 4: land files ready to go back to the Ministry (Planning Recommendation approved), and those already returned. */
    public function returns(Request $request): View
    {
        $tab = $request->query('tab') === 'returned' ? 'returned' : 'ready';
        $files = FileIndexing::query()->live()->intake()->with('tracker')
            ->when($tab === 'ready', fn ($w) => $w->where('stage', 'planning_recommendation'))
            ->when($tab === 'returned', fn ($w) => $w->where('stage', 'returned_to_ps'))
            ->latest('id')->paginate(20)->withQueryString();

        return view('kamma.shadow-files.returns', [
            'files' => $files, 'tab' => $tab,
            'counts' => [
                'ready' => FileIndexing::query()->live()->where('stage', 'planning_recommendation')->count(),
                'returned' => FileIndexing::query()->live()->where('stage', 'returned_to_ps')->count(),
            ],
        ]);
    }

    public function returnToPs(Request $request, int $file, DeskFlow $flow): RedirectResponse
    {
        $f = FileIndexing::query()->live()->findOrFail($file);
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);
        if (! in_array($f->stage, ['planning_recommendation', 'md'], true)) {
            throw ValidationException::withMessages(['remarks' => 'Only the MD KAMMA returns a land file to the PS, after the Planning Recommendation.']);
        }
        $flow->returnToPs($f, $data['remarks'] ?? null, $request->user());

        return back()->with('status', "{$f->file_number} detached from its shadow file and sent back to the Ministry of Land (PS).");
    }

    private function nextDesk(?string $stage): ?string
    {
        $at = array_search($stage, DeskFlow::ROUTE, true);

        return $at === false ? null : (DeskFlow::ROUTE[$at + 1] ?? 'returned_to_ps');
    }

    private function previousDesk(?string $stage): ?string
    {
        $at = array_search($stage, DeskFlow::ROUTE, true);

        return $at === false || $at === 0 ? null : DeskFlow::ROUTE[$at - 1];
    }
}
