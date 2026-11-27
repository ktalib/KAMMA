<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Models\Bpa\Application;
use App\Services\Bpa\CommissionBpFile;
use Illuminate\Http\Request;

/** Architecture > File Commissioning: give paid applications their BP file number. */
class CommissioningController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'commissioned' ? 'commissioned' : 'awaiting';
        $search = trim((string) $request->query('search', ''));

        $awaiting = fn ($q) => $q->whereNull('bp_file_no')->whereHas('bill', fn ($b) => $b->where('status', 'Paid'));

        $applications = Application::query()
            ->with('bill')
            ->when($tab === 'awaiting', $awaiting, fn ($q) => $q->whereNotNull('bp_file_no'))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    foreach (['bp_file_no', 'application_no', 'file_number', 'developer_name', 'plot_no', 'district', 'lga'] as $col) {
                        $q->orWhere($col, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderBy($tab === 'awaiting' ? 'id' : 'commissioned_at', $tab === 'awaiting' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'awaiting' => Application::query()->where($awaiting)->count(),
            'commissioned' => Application::whereNotNull('bp_file_no')->count(),
        ];

        return view('bpa.commissioning.index', compact('applications', 'tab', 'search', 'counts'));
    }

    public function store(Request $request, Application $application, CommissionBpFile $commission)
    {
        $application = $commission->handle($application, $request->user());

        return redirect()->route('bpa.commissioning.index')
            ->with('status', "{$application->application_no} commissioned as BP file {$application->bp_file_no}.");
    }
}
