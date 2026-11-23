<?php

namespace App\Http\Controllers\Ui;

use App\Services\Records\KlaesRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;
use App\Models\Dms\FileIndexing;
use App\Models\Kamma\KsiReport;

class CrmController
{
    public const PURPOSES = ['Planning application', 'Site inspection', 'Collect approval / permit', 'Payment enquiry', 'Complaint', 'Progress enquiry'];

    public function dashboard(KlaesRecords $records): View
    {
        [$stats, $error] = $records->attempt(fn () => $records->stats($records->definition('crm-entities')), null);
        $types = collect($stats['breakdowns'][0]['rows'] ?? []);

        try {
            $shadowFiles = FileIndexing::query()->live()->count();
            $reports = KsiReport::query()->count();
            $recent = FileIndexing::query()->live()->latest('id')->limit(6)->get([
                'file_number', 'file_title', 'plot_number', 'district', 'lga', 'location', 'created_at',
            ]);
        } catch (Throwable) {
            $shadowFiles = 0;
            $reports = 0;
            $recent = collect();
        }

        return view('ui.crm.dashboard', [
            'stats' => $stats,
            'error' => $error,
            'types' => $types->pluck('n', 'label')->all(),
            'shadowFiles' => $shadowFiles,
            'reports' => $reports,
            'recentFiles' => $recent,
            'slugs' => [
                'Individual' => 'individual',
                'Group' => 'group',
                'Multiple Owners' => 'multiple-owners',
                'Corporate' => 'corporate',
            ],
        ]);
    }

    public function appointments(): View
    {
        return view('ui.crm.appointments', ['purposes' => self::PURPOSES, 'units' => $this->units()]);
    }

    public function calendar(Request $request): View
    {
        try {
            $month = Carbon::createFromFormat('Y-m', (string) $request->query('month', now()->format('Y-m')))->startOfMonth();
        } catch (Throwable) {
            $month = now()->startOfMonth();
        }

        return view('ui.crm.calendar', ['month' => $month, 'purposes' => self::PURPOSES, 'units' => $this->units()]);
    }

    private function units(): array
    {
        try {
            return \App\Models\Tracking\Unit::query()->active()->pluck('name')->all();
        } catch (Throwable) {
            return [];
        }
    }
}
