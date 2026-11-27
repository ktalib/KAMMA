<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Models\Bpa\Application;
use App\Models\Bpa\Movement;
use App\Services\Bpa\MoveFile;
use App\Support\Bpa\BpaStage;
use Illuminate\Http\Request;

/**
 * Architecture > Building Plan: the commissioned BP files and their plans, for the Architectural Department.
 * A file the Director (Arch.) did not recommend comes back here, and is sent back to the Director once corrected.
 */
class BuildingPlanController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $returnedOnly = $request->boolean('returned');

        $applications = Application::query()
            ->withCount([
                'files as plan_count' => fn ($q) => $q->where('kind', 'plan'),
                'files as cad_count' => fn ($q) => $q->where('kind', 'cad'),
            ])
            ->whereNotNull('bp_file_no')
            ->when($returnedOnly, fn ($q) => $q->where('stage', BpaStage::ARCHITECTURE))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    foreach (['bp_file_no', 'application_no', 'file_number', 'developer_name', 'plot_no', 'district', 'lga'] as $col) {
                        $q->orWhere($col, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderByDesc('commissioned_at')
            ->paginate(20)
            ->withQueryString();

        // Why each returned file came back: the latest Not recommended on it.
        $returns = Movement::query()
            ->whereIn('bpa_application_id', $applications->getCollection()->where('stage', BpaStage::ARCHITECTURE)->pluck('id'))
            ->where('to_stage', BpaStage::ARCHITECTURE)->where('action', BpaStage::NOT_RECOMMENDED)
            ->orderByDesc('acted_at')->orderByDesc('id')->get()->unique('bpa_application_id')->keyBy('bpa_application_id');

        $returnedCount = Application::whereNotNull('bp_file_no')->where('stage', BpaStage::ARCHITECTURE)->count();

        return view('bpa.building-plans.index', compact('applications', 'search', 'returnedOnly', 'returns', 'returnedCount'));
    }

    public function resend(Request $request, Application $application, MoveFile $moves)
    {
        $data = $request->validateWithBag('resend', ['remarks' => ['nullable', 'string', 'max:2000']]);

        try {
            $moves->resend($application, $data['remarks'] ?? null, $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e->errorBag('resend');
        }

        return redirect()->route('bpa.building-plans.index')
            ->with('status', "{$application->bp_file_no} sent back to the Director (Arch.).");
    }
}
