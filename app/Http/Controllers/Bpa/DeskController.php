<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Models\Bpa\Application;
use App\Models\Bpa\Movement;
use App\Services\Bpa\MoveFile;
use App\Support\Bpa\BpaStage;
use Illuminate\Http\Request;

/**
 * Approvals > Director (Arch.) and Deputy Director (Arch.): Recommend or Not recommend each BP file,
 * with Awaiting / Decided tabs (the KLAES approval-tab pattern).
 */
class DeskController extends Controller
{
    public function index(Request $request, string $desk)
    {
        [$stage, $title] = BpaStage::desk($desk);
        $tab = $request->query('tab') === 'decided' ? 'decided' : 'awaiting';
        $search = trim((string) $request->query('search', ''));
        $decisions = [BpaStage::RECOMMENDED, BpaStage::NOT_RECOMMENDED];
        $decidedHere = fn ($q) => $q->whereHas('movements', fn ($m) => $m->where('from_stage', $stage)->whereIn('action', $decisions));

        $applications = Application::query()
            ->when($tab === 'awaiting', fn ($q) => $q->where('stage', $stage), $decidedHere)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    foreach (['bp_file_no', 'application_no', 'file_number', 'developer_name', 'plot_no', 'district', 'lga'] as $col) {
                        $q->orWhere($col, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // For each file: the latest decision taken at this desk, and when it arrived here.
        $ids = $applications->getCollection()->pluck('id');
        $latest = Movement::query()->whereIn('bpa_application_id', $ids)->where('from_stage', $stage)->whereIn('action', $decisions)
            ->orderByDesc('acted_at')->orderByDesc('id')->get()->unique('bpa_application_id')->keyBy('bpa_application_id');
        $arrived = Movement::query()->whereIn('bpa_application_id', $ids)->where('to_stage', $stage)
            ->orderByDesc('acted_at')->orderByDesc('id')->get()->unique('bpa_application_id')->keyBy('bpa_application_id');

        $counts = [
            'awaiting' => Application::where('stage', $stage)->count(),
            'decided' => Application::query()->where($decidedHere)->count(),
        ];

        // After a failed decision, reopen the pop-up on the same file.
        $reopen = $request->old('application_id') ? Application::find($request->old('application_id')) : null;
        [, , $onRecommend, $onRefuse] = BpaStage::desk($desk);

        return view('bpa.desks.index', compact(
            'applications', 'desk', 'stage', 'title', 'tab', 'search', 'counts', 'latest', 'arrived', 'reopen', 'onRecommend', 'onRefuse'
        ));
    }

    public function decide(Request $request, string $desk, Application $application, MoveFile $moves)
    {
        [, $title, $onRecommend, $onRefuse] = BpaStage::desk($desk);
        $data = $request->validateWithBag('decision', [
            'decision' => ['required', 'in:'.BpaStage::RECOMMENDED.','.BpaStage::NOT_RECOMMENDED],
            'remarks' => ['nullable', 'required_if:decision,'.BpaStage::NOT_RECOMMENDED, 'string', 'max:2000'],
        ], ['remarks.required_if' => 'Give the reason the file is not recommended.']);

        try {
            $moved = $moves->decide($application, $desk, $data['decision'], $data['remarks'] ?? null, $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e->errorBag('decision');
        }

        $word = $data['decision'] === BpaStage::RECOMMENDED ? 'recommended and sent to the' : 'not recommended and returned to';

        return redirect()->route('bpa.desks.index', $desk)
            ->with('status', "{$moved->bp_file_no} {$word} ".BpaStage::label($moved->stage).'.');
    }
}
