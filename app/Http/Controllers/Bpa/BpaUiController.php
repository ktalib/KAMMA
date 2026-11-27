<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Models\Bpa\Application;
use App\Support\Bpa\BpaStage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * KAMMA module screens after the Deputy Director (Arch.): the Building Plan Approval pipeline dashboard, the Zonal Office
 * (Head of Zonal Office assigns an officer, I-to-S style; the officer's Inspection with geocoded photos; Field Data
 * Integration from the mobile app), GIS BaseMap / Charting, KAMMA Memo and Final Conveyance (kadca-workflow skill).
 * The Zonal Office, inspection, Final Conveyance and Memo screens save through Bpa\ZonalController; BaseMap and Charting
 * are still UI first. No role gating (Zonal Office roles are on hold).
 */
class BpaUiController extends Controller
{
    /** The KAMMA column of the briefing diagram, in order. */
    public const PIPELINE = [
        ['Application & initial bill', 'file-plus-2', 'bpa.applications.index', BpaStage::ASSESSMENT],
        ['Architecture: payment & commissioning', 'drafting-compass', 'bpa.commissioning.index', BpaStage::ARCHITECTURE],
        ['Director (Arch.)', 'user-check', 'bpa.desks.index:director', BpaStage::DIRECTOR],
        ['Deputy Director (Arch.)', 'user-check', 'bpa.desks.index:deputy-director', BpaStage::DEPUTY_DIRECTOR],
        ['Zonal Office: assign officer', 'map-pinned', 'bpa.zonal-approval', BpaStage::ZONAL_OFFICE],
        ['Site visit & inspection', 'camera', 'bpa.inspection', BpaStage::INSPECTION],
        ['Head of Zonal Office: report', 'clipboard-check', 'bpa.zonal-approval', BpaStage::ZONAL_REVIEW],
        ['Final Conveyance', 'stamp', 'bpa.conveyance', BpaStage::CONVEYANCE],
        ['BPA approved', 'badge-check', 'bpa.conveyance', BpaStage::APPROVED],
    ];

    public function dashboard(): View
    {
        $byStage = Application::query()->selectRaw('stage, COUNT(*) as n')->groupBy('stage')->pluck('n', 'stage')->all();
        $commissioned = Application::query()->whereNotNull('bp_file_no')->count();

        return view('bpa.ui.dashboard', [
            'byStage' => $byStage,
            'total' => array_sum($byStage),
            'commissioned' => $commissioned,
            'mismatch' => Application::query()->where('land_use_check', 'Mismatch')->count(),
            'zonal' => $this->atZonalOffice()->take(8),
        ]);
    }

    public function basemap(): View
    {
        return view('bpa.ui.basemap', ['files' => $this->commissioned()]);
    }

    public function charting(): View
    {
        return view('bpa.ui.charting');
    }

    /** BP files on the Zonal Office desk (sent on by the Deputy Director). */
    private function atZonalOffice(): Collection
    {
        return Application::query()->where('stage', BpaStage::ZONAL_OFFICE)->latest('updated_at')->limit(200)->get();
    }

    private function commissioned(): Collection
    {
        return Application::query()->whereNotNull('bp_file_no')->latest('commissioned_at')->limit(200)->get();
    }
}
