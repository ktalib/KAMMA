<?php

namespace App\Services\Dms;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Page types for Page Typing: KLAES's own lookup (dbo.PageType / dbo.PageSubType, read-only) so KAMMA pages are
 * typed the same way as KLAES pages, plus the KAMMA shadow-file documents (KSI report, Planning Recommendation, bills…)
 * (config('kamma.dms_page_types')), merged without case/space duplicates. "Other" asks for the name (KLAES rule).
 *
 * @return array<string, list<string>> type => subtypes
 */
class PageTypes
{
    public function all(): array
    {
        return Cache::remember('kamma:dms:page-types', 600, function () {
            $types = [];
            try {
                $rows = DB::connection('klaes')->table(DB::raw($this->nolock('PageType', 'p')))
                    ->leftJoin(DB::raw($this->nolock('PageSubType', 's')), 's.PageTypeId', '=', 'p.id')
                    ->orderBy('p.id')->orderBy('s.id')
                    ->get(['p.PageType as type', 's.PageSubType as subtype']);
                foreach ($rows as $r) {
                    $this->add($types, (string) $r->type, $r->subtype);
                }
            } catch (\Throwable $e) {
                report($e); // KLAES lookup unreachable: carry on with KAMMA's own list
            }
            foreach (config('kamma.dms_page_types', []) as $type => $subtypes) {
                foreach ($subtypes ?: [null] as $sub) {
                    $this->add($types, $type, $sub);
                }
            }

            return array_map(fn ($subs) => array_values($subs), $types);
        });
    }

    /** True when the type (and subtype, if given) is on the list or is "Other". */
    public function valid(string $type, ?string $subtype): bool
    {
        if ($type === 'Other') {
            return true;
        }
        $all = $this->all();
        if (! array_key_exists($type, $all)) {
            return false;
        }

        return $subtype === null || $subtype === '' || $subtype === 'Other' || in_array($subtype, $all[$type], true);
    }

    private function add(array &$types, string $type, ?string $sub): void
    {
        $type = trim(preg_replace('/\s+/', ' ', $type));
        if ($type === '' || strcasecmp($type, 'Other') === 0) {
            return;
        }
        $key = $this->find(array_keys($types), $type) ?? $type;
        $types[$key] ??= [];
        $sub = trim(preg_replace('/\s+/', ' ', (string) $sub));
        if ($sub !== '' && strcasecmp($sub, 'Other') !== 0 && strcasecmp($sub, 'Others') !== 0 && $this->find($types[$key], $sub) === null) {
            $types[$key][] = $sub;
        }
    }

    private function find(array $list, string $value): ?string
    {
        foreach ($list as $v) {
            if (strcasecmp($v, $value) === 0) {
                return $v;
            }
        }

        return null;
    }

    /** "PageType as p with (nolock)": in T-SQL the hint comes after the alias. */
    private function nolock(string $table, string $alias): string
    {
        return DB::connection('klaes')->getDriverName() === 'sqlsrv' ? "{$table} as {$alias} with (nolock)" : "{$table} as {$alias}";
    }
}
