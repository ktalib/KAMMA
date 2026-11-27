<?php

namespace App\Services;

use App\Models\Bpa\Application;
use App\Models\Bpa\Bill as BpaBill;
use App\Models\Bpa\BpFile;
use App\Models\Bpa\Conveyance;
use App\Models\Bpa\Movement as BpaMovement;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\PageTyping;
use App\Models\Dms\Scanning;
use App\Models\Kamma\Bill as KammaBill;
use App\Models\Kamma\DeskMovement;
use App\Models\Kamma\KsiReport;
use App\Models\Kamma\Memo;
use App\Models\Kamma\TitleStatusUpdate;
use App\Models\Tracking\FileMovement;
use App\Models\Tracking\FileRequest;
use App\Models\Tracking\FileTracker;
use App\Support\Bpa\BpaStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * The main Dashboard (first sidebar item, also KAMMA > Dashboard): real figures from the app's own tables, replacing the
 * Breeze placeholder ("Total agents 24", "Active clients 128"). User 2026-11-25: one dashboard, "it's not accurate".
 * Each figure is read on its own, so a table that is not migrated yet shows "—" instead of breaking the page.
 */
class MainDashboard
{
    private Carbon $monthStart;

    public function build(): array
    {
        $this->monthStart = now()->startOfMonth();

        return [
            'headline' => [
                $this->card('Shadow files', fn () => FileIndexing::query()->where('is_deleted', false), 'files', 'emerald', route('dms.indexing.index', ['from' => 'kamma'])),
                $this->card('Planning recommendations', fn () => KsiReport::query(), 'clipboard-check', 'blue', route('kamma.pr.index', 'conversion')),
                $this->card('Building plan applications', fn () => Application::query(), 'drafting-compass', 'violet', route('bpa.applications.index')),
                $this->card('Portal requests', fn () => Application::query()->whereNotNull('portal_applicant_id'), 'globe', 'cyan', route('bpa.applications.index', ['source' => 'portal'])),
            ],
            'kamma' => [
                $this->card('Title status updates', fn () => TitleStatusUpdate::query(), 'file-pen-line', 'slate', route('kamma.title-status.index')),
                $this->card('KSI reports', fn () => KsiReport::query(), 'map-pin', 'amber', route('kamma.master-ksi.index')),
                $this->card('Memos', fn () => Memo::query(), 'file-text', 'blue', route('kamma.memos.index', 'conversion')),
                $this->card('Desk decisions', fn () => DeskMovement::query(), 'stamp', 'emerald', route('kamma.approvals.pr', 'conversion'), 'acted_at'),
            ],
            'permit' => [
                $this->card('BP files commissioned', fn () => BpFile::query()->where('origin', 'commissioned')->where('is_deleted', false), 'folder-plus', 'emerald', route('bpa.bp-files.commissioned')),
                $this->card('At the desks', fn () => Application::query()->whereNotIn('stage', [BpaStage::ASSESSMENT, BpaStage::APPROVED]), 'hourglass', 'amber', route('bpa.applications.index')),
                $this->card('Conveyances raised', fn () => Conveyance::query(), 'award', 'violet', route('bpa.conveyance')),
                $this->card('Land-use mismatches', fn () => Application::query()->where('land_use_check', 'Mismatch'), 'flag', 'rose', route('bpa.applications.index', ['land_use_check' => 'Mismatch'])),
            ],
            'records' => [
                $this->card('Pages scanned', fn () => Scanning::query()->where('is_deleted', false), 'scan-line', 'blue', route('dms.scanning.index')),
                $this->card('Pages typed', fn () => PageTyping::query(), 'list-checks', 'cyan', route('dms.pagetyping.index')),
                $this->card('Files tracked', fn () => FileTracker::query(), 'qr-code', 'violet', route('tracking.dashboard')),
                $this->card('File requests awaiting', fn () => FileRequest::query()->where('status', 'awaiting'), 'inbox', 'amber', route('tracking.search')),
            ],
            'money' => $this->money(),
            'activity' => $this->activity(),
        ];
    }

    /** A figure card: total, this month's count, link. A missing table gives null (shown as "—"). */
    private function card(string $label, callable $query, string $icon, string $tone, string $url, string $dateColumn = 'created_at'): array
    {
        try {
            /** @var Builder $q */
            $q = $query();
            $total = (clone $q)->count();
            $month = (clone $q)->where($dateColumn, '>=', $this->monthStart)->count();
        } catch (Throwable) {
            $total = $month = null;
        }

        return compact('label', 'total', 'month', 'icon', 'tone', 'url');
    }

    /** Bills of both sections (KAMMA processing / betterment bills and BPA bills): billed, paid, outstanding. */
    private function money(): array
    {
        $sum = function (callable $query, string $column): ?float {
            try {
                return (float) $query()->sum($column);
            } catch (Throwable) {
                return null;
            }
        };
        $add = fn (?float $a, ?float $b) => $a === null && $b === null ? null : (float) $a + (float) $b;

        $billed = $add($sum(fn () => KammaBill::query(), 'amount'), $sum(fn () => BpaBill::query(), 'amount'));
        $paid = $add($sum(fn () => KammaBill::query()->where('status', 'paid'), 'amount_paid'), $sum(fn () => BpaBill::query()->where('status', 'paid'), 'amount_paid'));
        $outstanding = $add($sum(fn () => KammaBill::query()->where('status', 'unpaid'), 'amount'), $sum(fn () => BpaBill::query()->where('status', 'unpaid'), 'amount'));
        $paidMonth = $add(
            $sum(fn () => KammaBill::query()->where('status', 'paid')->where('paid_on', '>=', $this->monthStart->toDateString()), 'amount_paid'),
            $sum(fn () => BpaBill::query()->where('status', 'paid')->where('paid_on', '>=', $this->monthStart->toDateString()), 'amount_paid'),
        );

        return [
            ['label' => 'Billed', 'value' => $billed, 'hint' => 'All bills raised', 'tone' => 'indigo', 'url' => route('kamma.bills.index')],
            ['label' => 'Paid', 'value' => $paid, 'hint' => 'Payments recorded', 'tone' => 'emerald', 'url' => route('kamma.payments.index')],
            ['label' => 'Paid this month', 'value' => $paidMonth, 'hint' => now()->format('F Y'), 'tone' => 'cyan', 'url' => route('kamma.payments.report')],
            ['label' => 'Outstanding', 'value' => $outstanding, 'hint' => 'Bills not yet paid', 'tone' => 'amber', 'url' => route('kamma.bills.index')],
        ];
    }

    /** The latest things done in the app, newest first: desk decisions, BPA moves, file movements, payments. */
    private function activity(): Collection
    {
        $take = function (callable $rows): Collection {
            try {
                return collect($rows());
            } catch (Throwable) {
                return collect();
            }
        };
        $stage = fn (?string $s) => $s ? (BpaStage::LABELS[$s] ?? Str::headline($s)) : '—';

        return collect()
            ->merge($take(fn () => DeskMovement::query()->latest('acted_at')->limit(8)->get()->map(fn ($m) => [
                'when' => $m->acted_at ?? $m->created_at, 'icon' => 'stamp', 'tone' => 'emerald', 'section' => 'KAMMA',
                'title' => Str::headline((string) $m->decision ?: 'Moved').' · '.Str::headline((string) $m->to_stage),
                'by' => $m->acted_by_name, 'url' => null,
            ])))
            ->merge($take(fn () => BpaMovement::query()->latest('acted_at')->limit(8)->get()->map(fn ($m) => [
                'when' => $m->acted_at ?? $m->created_at, 'icon' => 'drafting-compass', 'tone' => 'violet', 'section' => 'Development Permit',
                'title' => Str::headline((string) $m->action ?: 'Moved').' · '.$stage($m->to_stage),
                'by' => $m->acted_by_name, 'url' => route('bpa.applications.show', $m->bpa_application_id),
            ])))
            ->merge($take(fn () => FileMovement::query()->latest('acted_at')->limit(8)->get()->map(fn ($m) => [
                'when' => $m->acted_at ?? $m->created_at, 'icon' => 'qr-code', 'tone' => 'blue', 'section' => 'File Tracking',
                'title' => 'File '.Str::lower(Str::headline((string) $m->action)), 'by' => $m->acted_by_name, 'url' => route('tracking.dashboard'),
            ])))
            ->merge($take(fn () => KammaBill::query()->where('status', 'paid')->latest('updated_at')->limit(8)->get()->map(fn ($b) => [
                'when' => $b->updated_at, 'icon' => 'banknote', 'tone' => 'amber', 'section' => 'KAMMA',
                'title' => 'Payment ₦'.number_format((float) $b->amount_paid, 2).' · '.$b->file_number, 'by' => $b->payer_name, 'url' => route('kamma.payments.index'),
            ])))
            ->merge($take(fn () => BpaBill::query()->where('status', 'paid')->latest('updated_at')->limit(8)->get()->map(fn ($b) => [
                'when' => $b->updated_at, 'icon' => 'banknote', 'tone' => 'amber', 'section' => 'Development Permit',
                'title' => 'Payment ₦'.number_format((float) $b->amount_paid, 2).' · '.$b->bill_no, 'by' => $b->payer_name, 'url' => route('bpa.payments.index'),
            ])))
            ->filter(fn ($row) => $row['when'] !== null)
            ->sortByDesc(fn ($row) => Carbon::parse($row['when'])->getTimestamp())
            ->take(8)
            ->values();
    }
}
