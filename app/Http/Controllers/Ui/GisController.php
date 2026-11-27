<?php

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Controller;
use App\Services\Records\KlaesRecords;
use Illuminate\View\View;

/**
 * The GIS block: Survey Plan Extraction (layout copied from KLAES survey_plan_extraction), the GIS map viewer, GIS Approvals
 * and GIS Reports. KLAES's GIS Approvals and GIS Reports menu items are dead links (/gis/approvals, #), so those two are
 * fresh designs in KLAES style. Reports read KLAES gisCapture and eRegistry read-only.
 */
class GisController extends Controller
{
    public function extraction(): View
    {
        return view('ui.gis.extraction');
    }

    public function map(): View
    {
        return view('ui.gis.map');
    }

    public function approvals(): View
    {
        return view('ui.gis.approvals');
    }

    public function reports(KlaesRecords $records): View
    {
        $sets = [];
        $unreadable = [];
        foreach (['gis-records', 'e-registry', 'site-plans'] as $key) {
            $def = $records->definition($key);
            [$stats, $error] = $records->attempt(fn () => $records->stats($def), null);
            $sets[$key] = ['def' => $def, 'stats' => $stats];
            if ($error) {
                $unreadable[] = $def['title'];
            }
        }

        return view('ui.gis.reports', ['sets' => $sets, 'unreadable' => $unreadable]);
    }
}
