<?php

namespace App\Http\Controllers\Kamma;

use App\Http\Controllers\Controller;
use App\Models\Kamma\KsiReport;
use App\Models\Kamma\Memo;
use App\Services\Kamma\MdApprovals;
use App\Support\KammaKinds;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * MD KAMMA approvals (KLAES PP Director approvals: Planning Recommendation Approval and the Physical Planning Memo; the
 * Director of Physical Planning is the MD KAMMA). Tabs Awaiting / Approved / Declined (the KLAES approval-tab pattern);
 * the decision opens in a pop-up from the row menu. Decisions: App\Services\Kamma\MdApprovals.
 */
class ApprovalController extends Controller
{
    private const TABS = ['awaiting', 'approved', 'declined'];

    public function masterKsi(Request $request): View
    {
        return $this->reports($request, 'master');
    }

    public function pr(Request $request, string $kind): View
    {
        return $this->reports($request, $kind);
    }

    public function memo(Request $request, string $kind): View
    {
        $k = KammaKinds::get($kind);
        abort_unless(in_array($kind, KammaKinds::prKinds(), true), 404);
        $tab = $this->tab($request);
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $base = fn () => Memo::query()->where('kind', $kind);
        $status = fn (string $t) => $t === 'awaiting' ? 'submitted' : $t;

        $memos = $base()->with('report')->where('status', $status($tab))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('memo_no', 'like', "%{$q}%")->orWhere('file_number', 'like', "%{$q}%")
                ->orWhere('applicant_name', 'like', "%{$q}%")))
            ->latest('id')->paginate(20)->withQueryString();

        return view('kamma.approvals', [
            'type' => 'memo', 'k' => $k, 'kind' => $kind, 'tab' => $tab, 'q' => $q, 'rows' => $memos,
            'counts' => collect(self::TABS)->mapWithKeys(fn ($t) => [$t => $base()->where('status', $status($t))->count()])->all(),
            'route' => 'kamma.approvals.memo',
        ]);
    }

    public function decide(Request $request, int $report, MdApprovals $approvals): RedirectResponse
    {
        $d = $request->validate([
            'decision' => ['required', Rule::in(MdApprovals::DECISIONS)],
            'remarks' => ['nullable', 'required_if:decision,declined', 'string', 'max:2000'],
        ], ['remarks.required_if' => 'Give the reason for declining.']);

        $r = $approvals->decideReport(KsiReport::query()->findOrFail($report), $d['decision'], $d['remarks'] ?? null, $request->user());

        return back()->with('status', "Planning Recommendation on {$r->file_number} {$r->md_decision} by the MD KAMMA.");
    }

    public function decideMemo(Request $request, int $memo, MdApprovals $approvals): RedirectResponse
    {
        $d = $request->validate([
            'decision' => ['required', Rule::in(MdApprovals::DECISIONS)],
            'md_recommendation' => ['nullable', 'required_if:decision,declined', 'string', 'max:5000'],
        ], ['md_recommendation.required_if' => 'Give the reason for declining.']);

        $m = $approvals->decideMemo(Memo::query()->findOrFail($memo), $d['decision'], $d['md_recommendation'] ?? null, $request->user());

        return back()->with('status', "Memo {$m->memo_no} {$m->status} by the MD KAMMA.");
    }

    private function reports(Request $request, string $kind): View
    {
        $k = KammaKinds::get($kind);
        $tab = $this->tab($request);
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $base = fn () => KsiReport::query()->where('kind', $kind);
        $scope = fn ($w, string $t) => $t === 'awaiting'
            ? $w->where('status', 'submitted')->whereNull('md_decision')
            : $w->where('md_decision', $t);

        $reports = $scope($base(), $tab)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('file_number', 'like', "%{$q}%")->orWhere('applicant_name', 'like', "%{$q}%")
                ->orWhere('plot_number', 'like', "%{$q}%")->orWhere('district', 'like', "%{$q}%")))
            ->latest('id')->paginate(20)->withQueryString();

        return view('kamma.approvals', [
            'type' => 'report', 'k' => $k, 'kind' => $kind, 'tab' => $tab, 'q' => $q, 'rows' => $reports,
            'counts' => collect(self::TABS)->mapWithKeys(fn ($t) => [$t => $scope($base(), $t)->count()])->all(),
            'route' => $kind === 'master' ? 'kamma.approvals.master-ksi' : 'kamma.approvals.pr',
        ]);
    }

    private function tab(Request $request): string
    {
        return in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'awaiting';
    }
}
