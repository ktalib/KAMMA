<?php

namespace App\Services\Klaes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The KSI form's dropdowns, read from KLAES's own JSI lookups (read-only 'klaes' connection, NOLOCK), so a KAMMA
 * Site Inspection offers exactly what KLAES Physical Planning's JSI offers (KLAES rule 6: reuse existing lookup
 * tables, no new option source):
 *   dbo.land_uses (detailed land use) → dbo.jsi_lut_purposes (by land_use_id), dbo.jsi_lut_road_categories,
 *   dbo.jsi_lut_reservations, dbo.land_use_types, dbo.pp_betterment_rates, dbo.districts, dbo.lgas.
 * KLAES's own "OTHER" / "OTHERS" rows are dropped and exactly one "Other" is added by the form (it reveals a required
 * Specify box). If KLAES cannot be read the lists fall back to a short built-in copy, so the screen still opens.
 */
class KsiLookups
{
    /** @return list<array{id:int,name:string}> */
    public function landUses(): array
    {
        return $this->rows('ksi_land_uses', "select id, name from land_uses with (nolock) where is_active = 1 or is_active is null order by name",
            [['id' => 1, 'name' => 'Agricultural'], ['id' => 2, 'name' => 'Commercial'], ['id' => 3, 'name' => 'Industrial'], ['id' => 4, 'name' => 'Residential']]);
    }

    /** @return list<array{id:int,name:string,land_use_id:int}> */
    public function purposes(): array
    {
        return $this->rows('ksi_purposes', "select id, name, land_use_id from jsi_lut_purposes with (nolock) where is_active = 1 or is_active is null order by name",
            [['id' => 1, 'name' => 'RES-Layout', 'land_use_id' => 4], ['id' => 2, 'name' => 'COM-Shops & offices', 'land_use_id' => 2]]);
    }

    /** @return list<array{id:int,name:string}> */
    public function roadCategories(): array
    {
        return $this->rows('ksi_roads', "select id, name from jsi_lut_road_categories with (nolock) where is_active = 1 or is_active is null order by id",
            [['id' => 1, 'name' => 'LOCAL ROADS (Truck c) also know as collector Roads.'], ['id' => 2, 'name' => 'RURAL ROADS (Feeder/Access road)']]);
    }

    /** @return list<array{id:int,name:string}> */
    public function reservations(): array
    {
        return $this->rows('ksi_reservations', "select id, name from jsi_lut_reservations with (nolock) where is_active = 1 or is_active is null order by id",
            [['id' => 1, 'name' => 'LOCAL ROADS ( 30m R.O.W and 15m R.R)'], ['id' => 2, 'name' => 'RESIDENTIAL NEIGHBOURHOOD (10m R.O.W)']]);
    }

    /** @return list<array{id:int,name:string}> */
    public function landUseTypes(): array
    {
        return $this->rows('ksi_land_use_types', "select id, name from land_use_types with (nolock) where is_active = 1 or is_active is null order by name",
            [['id' => 1, 'name' => 'RESIDENTIAL'], ['id' => 2, 'name' => 'COMMERCIAL'], ['id' => 3, 'name' => 'INDUSTRIAL'], ['id' => 4, 'name' => 'AGRICULTURAL']]);
    }

    /**
     * KLAES Physical Planning's betterment rates (pp_betterment_rates). KAMMA's own rate table is an open question,
     * so the bill screen shows these, read-only.
     *
     * @return list<array{category:string,base_area:float,base_fee:float,rate_per_extra_sqm:float}>
     */
    public function bettermentRates(): array
    {
        return array_map(fn ($r) => [
            'category' => (string) $r['category'],
            'base_area' => (float) $r['base_area'],
            'base_fee' => (float) $r['base_fee'],
            'rate_per_extra_sqm' => (float) $r['rate_per_extra_sqm'],
        ], $this->rows('ksi_betterment_rates', 'select category, base_area, base_fee, rate_per_extra_sqm from pp_betterment_rates with (nolock) where is_active = 1 order by category', [
            ['category' => 'Agricultural', 'base_area' => 5000, 'base_fee' => 25000, 'rate_per_extra_sqm' => 1],
            ['category' => 'Commercial', 'base_area' => 100, 'base_fee' => 35000, 'rate_per_extra_sqm' => 5],
            ['category' => 'Industrial', 'base_area' => 100, 'base_fee' => 35000, 'rate_per_extra_sqm' => 5],
            ['category' => 'Petrol Filling Station', 'base_area' => 100, 'base_fee' => 50000, 'rate_per_extra_sqm' => 5],
            ['category' => 'Residential', 'base_area' => 100, 'base_fee' => 15000, 'rate_per_extra_sqm' => 1],
        ]));
    }

    /** True when the rates above came from KLAES rather than the built-in copy. */
    public function ratesFromKlaes(): bool
    {
        return $this->rows('ksi_betterment_rates_live', 'select top 1 id from pp_betterment_rates with (nolock)', []) !== [];
    }

    /** @return list<string> */
    public function districts(): array
    {
        return array_column($this->rows('ksi_districts', 'select name from districts with (nolock) where is_active = 1 order by name', []), 'name');
    }

    /** @return list<string> */
    public function lgas(): array
    {
        return array_column($this->rows('ksi_lgas', 'select name from lgas with (nolock) where is_active = 1 order by name', []), 'name');
    }

    /**
     * @param  list<array<string, mixed>>  $fallback
     * @return list<array<string, mixed>>
     */
    private function rows(string $key, string $sql, array $fallback): array
    {
        try {
            return Cache::remember('kamma:'.$key, 600, function () use ($sql) {
                $k = DB::connection('klaes');
                if ($k->getDriverName() !== 'sqlsrv') {
                    $sql = str_replace(' with (nolock)', '', preg_replace('/select top \d+ /i', 'select ', $sql));
                }

                return array_values(array_filter(
                    array_map(fn ($r) => (array) $r, $k->select($sql)),
                    // KLAES's own catch-all rows: the form adds exactly one "Other" itself.
                    fn ($r) => ! isset($r['name']) || ! preg_match('/^\s*others?\s*$/i', (string) $r['name'])
                ));
            });
        } catch (Throwable) {
            return $fallback;
        }
    }
}
