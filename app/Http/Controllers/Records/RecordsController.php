<?php

namespace App\Http\Controllers\Records;

use App\Http\Controllers\Controller;
use App\Services\Records\KlaesRecords;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only KLAES record screens (Information Products, Land Records, DMS registers, GIS, CRM):
 * a dashboard band, search and a paginated table over the same KLAES tables KLAES's own screens read.
 */
class RecordsController extends Controller
{
    /** CRM menu items: slug => KLAES entities.entity_type. */
    public const CRM_TYPES = [
        'individual' => 'Individual',
        'group' => 'Group',
        'multiple-owners' => 'Multiple Owners',
        'corporate' => 'Corporate',
    ];

    public function __construct(private KlaesRecords $records)
    {
    }

    public function show(Request $request, string $dataset): View
    {
        return $this->screen($request, $this->records->definition($dataset));
    }

    public function crm(Request $request, string $type): View
    {
        abort_unless(isset(self::CRM_TYPES[$type]), 404);
        $label = self::CRM_TYPES[$type];
        $def = $this->records->definition('crm-entities');
        $def['title'] = $label === 'Corporate' ? 'Corporate' : 'Person: '.$label;
        $def['subtitle'] = $label.' customers in the KLAES customer master';
        $def['filters'] = [];

        return $this->screen($request, $def, ['entity_type' => $label], ['crm_type' => $type]);
    }

    /** Information Products > Dashboard: the four products side by side. */
    public function informationProducts(): View
    {
        return $this->groupDashboard(['rofo', 'op', 'site-plans', 'cofo'], ['title' => 'Information Products', 'icon' => 'files', 'tone' => 'emerald',
            'subtitle' => 'Title documents and plans KLAES has produced: Letters of Grant / RofO, Occupancy Permits, Site / Parcel Plans and Certificates of Occupancy',
            'tables' => 'pra, instrument_capture, survey_plan_uploads, CofO_staging']);
    }

    /** Land Records > Dashboard (user 2026-11-23: Information Products and Land Records "each with a dashboard and search"). */
    public function landRecords(): View
    {
        return $this->groupDashboard(['file-records', 'letter-of-grant', 'post-cofo', 'file-status'], ['title' => 'Land Records', 'icon' => 'book-open', 'tone' => 'blue',
            'subtitle' => 'Land files, Letters of Grant, Post-CofO instruments and title status as recorded in KLAES',
            'tables' => 'file_indexings, land recommendations / pra, title_status_applications']);
    }

    public function dashboard(Request $request, string $group): View
    {
        return match ($group) {
            'products' => $this->informationProducts(),
            'land' => $this->landRecords(),
            default => abort(404),
        };
    }

    public function index(Request $request, string $group, string $dataset): View
    {
        return $this->show($request, $dataset);
    }

    private function groupDashboard(array $keys, array $page): View
    {
        $sets = [];
        $unreadable = [];
        foreach ($keys as $key) {
            $def = $this->records->definition($key);
            [$stats, $error] = $this->records->attempt(fn () => $this->records->stats($def), null);
            $sets[$key] = ['def' => $def, 'stats' => $stats];
            if ($error) {
                $unreadable[] = $def['title'];
            }
        }

        return view('records.information-products', ['sets' => $sets, 'unreadable' => $unreadable] + $page);
    }

    private function screen(Request $request, array $def, array $fixed = [], array $extra = []): View
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $filters = [];
        foreach (array_keys($def['filters']) as $name) {
            $filters[$name] = mb_substr(trim((string) $request->query($name, '')), 0, 150);
        }
        $page = max(1, (int) $request->query('page', 1));

        [$stats, $statsError] = $this->records->attempt(fn () => $this->records->stats($def, $fixed), null);
        [$rows, $rowsError] = $this->records->attempt(fn () => $this->records->rows($def, $q, $filters, $fixed, $page), null);
        $options = [];
        foreach ($def['filters'] as $name => [, $expr]) {
            [$options[$name]] = $this->records->attempt(fn () => $this->records->filterOptions($def, $expr), []);
        }

        return view('records.index', [
            'def' => $def,
            'stats' => $stats,
            'rows' => $rows,
            'q' => $q,
            'filters' => $filters,
            'options' => $options,
            'error' => $statsError ?? $rowsError,
            'records' => $this->records,
        ] + $extra);
    }
}
