<?php

namespace App\Services\Ui;

use App\Support\KammaDesk;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * What KAMMA staff did, read from KAMMA's own records (copied from KADCA's feed; there is no separate activity log yet):
 * shadow files commissioned, desk decisions, KSI reports, bills raised and payments recorded, file movements, scans and
 * page typing. Each source is read on its own so a table that is not created yet only drops that source.
 */
class ActivityFeed
{
    /** scope => modules shown */
    public const SCOPES = [
        'dms' => ['DMS'],
        'hr' => ['KAMMA', 'Tracking', 'DMS'],
        'admin' => ['KAMMA', 'Tracking', 'DMS'],
    ];

    /** @return Collection<int, array<string, mixed>> */
    public function recent(array $modules = ['KAMMA', 'Tracking', 'DMS'], ?Carbon $since = null, int $limit = 300): Collection
    {
        $since ??= now()->subDays(30);
        $events = collect();

        $sources = [
            'KAMMA' => [
                fn () => DB::table('desk_movements as m')->join('file_indexings as f', 'f.id', '=', 'm.file_indexing_id')
                    ->where('m.acted_at', '>=', $since)->latest('m.acted_at')->limit($limit)
                    ->get(['f.file_number as ref', 'm.decision as detail', 'm.acted_by_name as who', 'm.acted_at as at', 'm.to_stage'])
                    ->map(fn ($r) => $this->event($r, 'KAMMA', match ($r->detail) {
                        'forwarded' => 'Received from the PS', 'recommended' => 'Recommended', 'not_recommended' => 'Not recommended',
                        'returned' => 'Returned to the Ministry', default => ucfirst(str_replace('_', ' ', (string) $r->detail)),
                    }, 'stamp', 'To '.KammaDesk::label($r->to_stage))),
                fn () => DB::table('ksi_reports')->where('created_at', '>=', $since)->latest()->limit($limit)
                    ->get(['file_number as ref', 'kind as detail', 'created_by_name as who', 'created_at as at'])
                    ->map(fn ($r) => $this->event($r, 'KAMMA', 'KSI report entered', 'clipboard-check', strtoupper((string) $r->detail))),
                fn () => DB::table('bills')->where('created_at', '>=', $since)->latest()->limit($limit)
                    ->get(['bill_no as ref', 'bill_type as detail', 'created_by_name as who', 'created_at as at'])
                    ->map(fn ($r) => $this->event($r, 'KAMMA', 'Bill raised', 'receipt', ucfirst((string) $r->detail))),
                fn () => DB::table('bills')->whereNotNull('paid_on')->where('updated_at', '>=', $since)->latest('updated_at')->limit($limit)
                    ->get(['bill_no as ref', 'receipt_no as detail', 'created_by_name as who', 'updated_at as at'])
                    ->map(fn ($r) => $this->event($r, 'KAMMA', 'Payment recorded', 'banknote')),
            ],
            'Tracking' => [
                fn () => DB::table('file_movements as m')->join('file_trackers as t', 't.id', '=', 'm.file_tracker_id')
                    ->where('m.acted_at', '>=', $since)->latest('m.acted_at')->limit($limit)
                    ->get(['t.tracking_id as ref', 't.reference as detail', 'm.acted_by_name as who', 'm.acted_at as at', 'm.action'])
                    ->map(fn ($r) => $this->event($r, 'Tracking', 'File '.$r->action, 'route')),
            ],
            'DMS' => [
                fn () => DB::table('file_indexings')->where('created_at', '>=', $since)->latest()->limit($limit)
                    ->get(['file_number as ref', 'file_title as detail', 'matched_by_name as who', 'created_at as at'])
                    ->map(fn ($r) => $this->event($r, 'DMS', 'Shadow file commissioned', 'folder-input')),
                fn () => DB::table('scannings as s')->join('file_indexings as f', 'f.id', '=', 's.file_indexing_id')
                    ->where('s.created_at', '>=', $since)
                    ->groupBy('f.file_number', 's.upload_batch', 's.uploaded_by_name')
                    ->selectRaw('f.file_number as ref, COUNT(*) as detail, s.uploaded_by_name as who, MAX(s.created_at) as at')
                    ->orderByDesc('at')->limit($limit)->get()
                    ->map(fn ($r) => $this->event($r, 'DMS', 'Pages scanned', 'scan-line', $r->detail.' page(s)')),
                fn () => DB::table('page_typings as p')->join('file_indexings as f', 'f.id', '=', 'p.file_indexing_id')
                    ->where('p.created_at', '>=', $since)->latest('p.created_at')->limit($limit)
                    ->get(['f.file_number as ref', 'p.page_type as detail', 'p.typed_by_name as who', 'p.created_at as at'])
                    ->map(fn ($r) => $this->event($r, 'DMS', 'Page typed', 'type')),
            ],
        ];

        foreach ($modules as $module) {
            foreach ($sources[$module] ?? [] as $read) {
                try {
                    $events = $events->concat($read());
                } catch (Throwable) {
                    // that source's table is not there yet: leave it out
                }
            }
        }

        return $events->sortByDesc('at')->values()->take($limit);
    }

    private function event(object $r, string $module, string $action, string $icon, ?string $detail = null): array
    {
        return [
            'module' => $module,
            'action' => $action,
            'icon' => $icon,
            'ref' => $r->ref ?? null,
            'detail' => $detail ?? (is_scalar($r->detail ?? null) ? (string) $r->detail : null),
            'who' => $r->who ?: 'Unknown',
            'at' => Carbon::parse($r->at),
        ];
    }
}
