<?php

namespace App\Models\Bpa;

use Illuminate\Database\Eloquent\Model;

/**
 * One BP-[Year]-[Serial] number set aside for an existing BP file (kadca_bp_groupings), made by
 * `php artisan kamma:bp-grouping`. Status Available until a file is indexed (or commissioned) under it.
 * Each number carries its tracking ID (BP-TRK-…), the only place it is kept.
 */
class BpGrouping extends Model
{
    public const AVAILABLE = 'Available';
    public const INDEXED = 'Indexed';
    public const COMMISSIONED = 'Commissioned';

    public $timestamps = false;

    protected $table = 'bp_groupings';

    protected $guarded = ['id'];

    /** BP tracking IDs (user 2026-11-26): kept only on the grouping row and fetched by BP number. No 0/O, 1/I. */
    public const TRACKING_PREFIX = 'BP-TRK-';
    public const TRACKING_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public static function randomTrackingId(): string
    {
        $id = self::TRACKING_PREFIX;
        for ($i = 0; $i < 8; $i++) {
            $id .= self::TRACKING_ALPHABET[random_int(0, strlen(self::TRACKING_ALPHABET) - 1)];
        }

        return $id;
    }

    /** A tracking ID no grouping row holds yet. */
    public static function newTrackingId(): string
    {
        do {
            $id = self::randomTrackingId();
        } while (self::query()->where('tracking_id', $id)->exists());

        return $id;
    }

    /** The tracking ID of a BP number, read from the grouping. */
    public static function trackingIdFor(?string $bpFileNo): ?string
    {
        return $bpFileNo ? self::query()->where('bp_file_no', $bpFileNo)->value('tracking_id') : null;
    }

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }
}
