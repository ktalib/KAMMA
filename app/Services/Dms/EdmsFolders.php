<?php

namespace App\Services\Dms;

/**
 * KAMMA's EDMS folders, laid out like KLAES's (user 2026-11-25): a folder per stage, and in each a KAMMA folder, as
 * KLAES has Lands_Registry / ST_Registry. Blind scans use KAMMA_Raw, as KLAES uses Lands_Registry_Raw. All paths are
 * relative to the "edms" disk (KAMMA's own storage/app/private/EDMS), never the KLAES F:\storage.
 */
class EdmsFolders
{
    public const BLIND_SCAN = 'BLIND_SCAN';
    public const UPLOAD = 'UPLOAD';
    public const SCAN_UPLOAD = 'SCAN_UPLOAD';
    public const PAGETYPING = 'PAGETYPING';
    public const ARCHIVE = 'ARCHIVE_Doc_WARE';

    public const STAGES = [self::BLIND_SCAN, self::UPLOAD, self::SCAN_UPLOAD, self::PAGETYPING, self::ARCHIVE];

    /** KLAES: a file's own index-card folder. */
    public const INDEX_CARDS = 'INDEX_CARDS';

    /** The registry folder of a stage: BLIND_SCAN/KAMMA_Raw, SCAN_UPLOAD/KAMMA, ... */
    public static function root(string $stage): string
    {
        return $stage.'/'.($stage === self::BLIND_SCAN ? config('kamma.edms.raw', 'KAMMA_Raw') : config('kamma.edms.registry', 'KAMMA'));
    }

    /** A file's folder name (KLAES blindFolderName): anything but letters, digits, _ - . becomes _. Empty = unusable. */
    public static function folderName(string $fileNumber): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9_\-\.]/', '_', trim($fileNumber)), '._');
    }

    /** A file's folder in a stage: SCAN_UPLOAD/KAMMA/RES-2016-452 */
    public static function fileFolder(string $stage, string $fileNumber): string
    {
        return self::root($stage).'/'.self::folderName($fileNumber);
    }

    /** Every stage's KAMMA folder exists (made on first use, like KLAES's registry folders). */
    public static function ensure(\Illuminate\Contracts\Filesystem\Filesystem $disk): void
    {
        foreach (self::STAGES as $stage) {
            if (! $disk->exists(self::root($stage))) {
                $disk->makeDirectory(self::root($stage));
            }
        }
    }
}
