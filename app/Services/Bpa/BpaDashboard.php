<?php

namespace App\Services\Bpa;

use App\Models\Bpa\Application;
use App\Models\Bpa\Bill;
use App\Support\Bpa\BpaStage;
use Illuminate\Database\Eloquent\Builder;

/**
 * The KAMMA dashboard, borrowed from KLAES's module dashboards (klas App\Services\ModuleDashboards +
 * module_dashboards/show): figure cards with "+N this month" that open their list, and a "Go to" panel.
 * KAMMA's figures are small, so they are counted live rather than cached.
 */
class BpaDashboard
{
    /** @return array{cards: list<array<string, mixed>>, money: list<array<string, mixed>>, links: list<array<string, string>>} */
    public function build(): array
    {
        $monthStart = now()->startOfMonth();
        $apps = fn () => Application::query();

        $cards = [
            $this->card('Applications', $apps(), 'Building Plan Approval applications received', 'document', 'indigo',
                route('bpa.applications.index'), 'created_at'),
            $this->card('Awaiting payment', Bill::query()->initial()->where('status', 'Unpaid'), 'Application bills not yet paid', 'cash', 'amber',
                route('bpa.payments.index')),
            $this->card('Awaiting commissioning', $apps()->whereNull('bp_file_no')->whereHas('bill', fn ($b) => $b->where('status', 'Paid')),
                'Paid, waiting for a BP file number', 'folder-plus', 'amber', route('bpa.commissioning.index')),
            $this->card('BP files', $apps()->whereNotNull('bp_file_no'), 'Commissioned BP files', 'building', 'green',
                route('bpa.building-plans.index'), 'commissioned_at'),
            $this->card('With Director (Arch.)', $apps()->where('stage', BpaStage::DIRECTOR), 'Waiting for a recommendation', 'check-circle', 'blue',
                route('bpa.desks.index', 'director')),
            $this->card('With Deputy Director (Arch.)', $apps()->where('stage', BpaStage::DEPUTY_DIRECTOR), 'Waiting for a recommendation', 'check-circle', 'blue',
                route('bpa.desks.index', 'deputy-director')),
            $this->card('Returned to Architecture', $apps()->whereNotNull('bp_file_no')->where('stage', BpaStage::ARCHITECTURE),
                'Not recommended by the Director (Arch.), to correct', 'arrow-uturn', 'rose', route('bpa.building-plans.index', ['returned' => 1])),
            $this->card('Land-use flagged', $apps()->where('land_use_check', 'Mismatch'), 'Proposed use differs from the land file', 'flag', 'rose',
                route('bpa.applications.index', ['land_use_check' => 'Mismatch']), 'created_at'),
            $this->card('With Zonal Office', $apps()->where('stage', BpaStage::ZONAL_OFFICE), 'Recommended by the Deputy Director (Arch.)', 'map-pin', 'purple', null),
        ];

        $money = [
            ['label' => 'Fees billed', 'total' => (float) Bill::query()->initial()->sum('amount'), 'hint' => 'All application bills', 'colour' => 'indigo',
                'month' => (float) Bill::query()->initial()->where('created_at', '>=', $monthStart)->sum('amount')],
            ['label' => 'Fees paid', 'total' => (float) Bill::query()->initial()->where('status', 'Paid')->sum('amount_paid'), 'hint' => 'Payments recorded', 'colour' => 'green',
                'month' => (float) Bill::query()->initial()->where('status', 'Paid')->where('paid_on', '>=', $monthStart->toDateString())->sum('amount_paid')],
            ['label' => 'Fees outstanding', 'total' => (float) Bill::query()->initial()->where('status', 'Unpaid')->sum('amount'), 'hint' => 'Bills not yet paid', 'colour' => 'amber',
                'month' => null],
        ];

        $links = [
            ['label' => 'New application', 'url' => route('bpa.applications.create'), 'icon' => 'plus'],
            ['label' => 'Applications', 'url' => route('bpa.applications.index'), 'icon' => 'document'],
            ['label' => 'Payment', 'url' => route('bpa.payments.index'), 'icon' => 'cash'],
            ['label' => 'File Commissioning', 'url' => route('bpa.commissioning.index'), 'icon' => 'folder-plus'],
            ['label' => 'Director (Arch.) approval', 'url' => route('bpa.desks.index', 'director'), 'icon' => 'check-circle'],
            ['label' => 'Deputy Director (Arch.) approval', 'url' => route('bpa.desks.index', 'deputy-director'), 'icon' => 'check-circle'],
        ];

        return compact('cards', 'money', 'links');
    }

    private function card(string $label, Builder $query, string $hint, string $icon, string $colour, ?string $url, ?string $dateColumn = null): array
    {
        return [
            'label' => $label,
            'total' => (clone $query)->count(),
            'month' => $dateColumn ? (clone $query)->where($dateColumn, '>=', now()->startOfMonth())->count() : null,
            'hint' => $hint,
            'icon' => $icon,
            'colour' => $colour,
            'url' => $url,
        ];
    }
}
