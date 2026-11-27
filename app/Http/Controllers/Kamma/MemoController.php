<?php

namespace App\Http\Controllers\Kamma;

use App\Http\Controllers\Controller;
use App\Models\Kamma\KsiReport;
use App\Models\Kamma\Memo;
use App\Services\Kamma\MdApprovals;
use App\Support\KammaKinds;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * KAMMA Memo (KLAES Physical Planning Memo / "ST Applications for Memo", per kind): tab "Awaiting memo" lists the
 * Planning Recommendations the MD KAMMA approved that have no memo yet (Generate Memo pop-up), tab "Memos" lists the
 * memos raised with their MD KAMMA status. A memo opens as an A4 page that prints. Rules: App\Services\Kamma\MdApprovals.
 */
class MemoController extends Controller
{
    public function index(Request $request, string $kind): View
    {
        $k = $this->kind($kind);
        $tab = $request->query('tab') === 'memos' ? 'memos' : 'awaiting';
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $awaiting = fn () => KsiReport::query()->where('kind', $kind)->where('md_decision', 'approved')
            ->whereNotIn('id', Memo::query()->whereNotNull('ksi_report_id')->select('ksi_report_id'));
        $search = fn ($w, array $cols) => $w->when($q !== '', fn ($x) => $x->where(function ($y) use ($cols, $q) {
            foreach ($cols as $c) {
                $y->orWhere($c, 'like', "%{$q}%");
            }
        }));

        $rows = $tab === 'awaiting'
            ? $search($awaiting(), ['file_number', 'applicant_name', 'plot_number', 'district'])->latest('md_decided_at')->paginate(20)->withQueryString()
            : $search(Memo::query()->with('report')->where('kind', $kind), ['memo_no', 'file_number', 'applicant_name'])->latest('id')->paginate(20)->withQueryString();

        return view('kamma.memos', [
            'k' => $k, 'kind' => $kind, 'tab' => $tab, 'q' => $q, 'rows' => $rows,
            'counts' => ['awaiting' => $awaiting()->count(), 'memos' => Memo::query()->where('kind', $kind)->count()],
        ]);
    }

    public function store(Request $request, string $kind, MdApprovals $approvals): RedirectResponse
    {
        $this->kind($kind);
        $d = $request->validate([
            'ksi_report_id' => ['required', 'integer'],
            'planner_recommendation' => ['required', 'string', 'max:5000'],
            'planner_recommendation_date' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'planner_recommendation.required' => "Write the planner's recommendation.",
        ]);

        $report = KsiReport::query()->where('kind', $kind)->findOrFail($d['ksi_report_id']);
        $memo = $approvals->raiseMemo($report, $d['planner_recommendation'], $d['planner_recommendation_date'], $request->user());

        return redirect()->route('kamma.memos.index', [$kind, 'tab' => 'memos'])->with('status', "Memo {$memo->memo_no} raised on {$memo->file_number} and sent to the MD KAMMA.");
    }

    public function show(string $kind, int $memo): View
    {
        $k = $this->kind($kind);

        return view('kamma.memo-show', ['k' => $k, 'kind' => $kind, 'm' => Memo::query()->with('report')->where('kind', $kind)->findOrFail($memo)]);
    }

    private function kind(string $kind): array
    {
        $k = KammaKinds::get($kind);
        abort_unless(in_array($kind, KammaKinds::prKinds(), true), 404);

        return $k;
    }
}
