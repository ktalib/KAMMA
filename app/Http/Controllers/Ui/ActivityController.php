<?php

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Controller;
use App\Services\Ui\ActivityFeed;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Activity Monitoring (DMS, HR Manager and System Admin) and System Admin > Activity Logs, from KAMMA's own records
 * (KLAES's equivalents read user_activity_logs, which KAMMA does not write).
 */
class ActivityController extends Controller
{
    public function monitoring(Request $request, ActivityFeed $feed): View
    {
        $scope = in_array($request->query('scope'), array_keys(ActivityFeed::SCOPES), true) ? $request->query('scope') : 'admin';
        $days = in_array((int) $request->query('days'), [1, 7, 30], true) ? (int) $request->query('days') : 7;
        $events = $feed->recent(ActivityFeed::SCOPES[$scope], now()->subDays($days)->startOfDay(), 1000);

        $byUser = $events->groupBy('who')->map(fn ($e, $who) => [
            'who' => $who,
            'count' => $e->count(),
            'last' => $e->max('at'),
            'actions' => $e->countBy('action')->sortDesc()->take(3)->all(),
        ])->sortByDesc('count')->values();

        $perDay = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $perDay[now()->subDays($i)->format('Y-m-d')] = 0;
        }
        foreach ($events as $e) {
            $k = $e['at']->format('Y-m-d');
            if (isset($perDay[$k])) {
                $perDay[$k]++;
            }
        }

        return view('ui.activity.monitoring', [
            'scope' => $scope, 'days' => $days, 'events' => $events->take(15), 'byUser' => $byUser,
            'byAction' => $events->countBy('action')->sortDesc()->all(), 'perDay' => $perDay, 'total' => $events->count(),
        ]);
    }

    public function logs(Request $request, ActivityFeed $feed): View
    {
        $module = in_array($request->query('module'), ['KAMMA', 'Tracking', 'DMS'], true) ? $request->query('module') : null;
        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $events = $feed->recent($module ? [$module] : ['KAMMA', 'Tracking', 'DMS'], now()->subDays(90), 2000)
            ->when($q !== '', fn ($c) => $c->filter(fn ($e) => str_contains(mb_strtolower(implode(' ', [$e['who'], $e['action'], $e['ref'], $e['detail']])), $q)))
            ->values();
        $page = max(1, (int) $request->query('page', 1));
        $rows = new LengthAwarePaginator($events->forPage($page, 25)->values(), $events->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('ui.activity.logs', ['rows' => $rows, 'module' => $module, 'q' => $request->query('q', '')]);
    }
}
