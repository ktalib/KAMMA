<?php

namespace App\Services\Dms;

use App\Models\Dms\BlindScan;
use App\Models\Dms\FileIndexing;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * DMS > Blind Scanning, copied from KLAES BlindScanningController (user 2026-11-25): scanned folders are migrated from
 * the PC into EDMS/BLIND_SCAN/KAMMA_Raw/<file no.>, one kamma_blind_scans row per page, before Scan Upload takes them
 * into the file. KLAES rules kept: a Full file keeps its A4 / A3 / INDEX_CARDS folders; an LF Scan goes to A3 and an
 * Index Cards upload to INDEX_CARDS (pages from the wrong folder are rejected); nothing is overwritten (an identical
 * re-send is skipped, the same name with other content is kept as "name (2).jpg").
 */
class BlindScans
{
    public const FULL = 'full';
    public const LF = 'lf';
    public const IC = 'ic';

    public function disk(): Filesystem
    {
        return Storage::disk(config('kamma.dms_disk'));
    }

    /**
     * One batch of a Migrate to Server.
     *
     * @param  list<UploadedFile>  $files
     * @param  list<string|null>  $paths  each file's path inside the picked folder (A4/p1.jpg), without the folder itself
     * @return array{saved: list<string>, skipped: list<string>, renamed: list<string>, rejected: list<string>, failed: list<string>}
     */
    public function store(FileIndexing $indexing, array $files, array $paths, string $type, string $batch, User $officer): array
    {
        if ($indexing->is_deleted) {
            throw ValidationException::withMessages(['file_indexing_id' => 'That indexed file has been removed.']);
        }
        $folderName = EdmsFolders::folderName($indexing->file_number);
        if ($folderName === '') {
            throw ValidationException::withMessages(['file_indexing_id' => 'That file number cannot be used as a folder name.']);
        }

        $disk = $this->disk();
        EdmsFolders::ensure($disk);
        $folder = EdmsFolders::root(EdmsFolders::BLIND_SCAN).'/'.$folderName;
        $result = ['saved' => [], 'skipped' => [], 'renamed' => [], 'rejected' => [], 'failed' => []];

        foreach (array_values($files) as $i => $file) {
            $path = $this->targetPath($type, $paths[$i] ?? null, $file);
            if ($path === null) {
                $result['rejected'][] = (string) ($paths[$i] ?? $file->getClientOriginalName());

                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (! in_array($ext, (array) config('kamma.edms.extensions'), true)) {
                $result['rejected'][] = $path;

                continue;
            }
            if (! $file->isValid()) {
                $result['failed'][] = $path;

                continue;
            }

            try {
                [$status, $final] = $this->place($disk, $folder, $path, $file);
                if ($status === 'skipped') {
                    $result['skipped'][] = $path;

                    continue;
                }
                BlindScan::query()->create([
                    'file_indexing_id' => $indexing->getKey(),
                    'file_number' => $indexing->file_number,
                    'folder' => $folder,
                    'relative_path' => $final,
                    'original_filename' => mb_substr($file->getClientOriginalName(), 0, 300),
                    'paper_size' => $this->paperOf($final),
                    'upload_type' => $type,
                    'mime' => $file->getMimeType(),
                    'file_size' => (int) $file->getSize(),
                    'upload_batch' => mb_substr($batch, 0, 40),
                    'status' => BlindScan::PENDING,
                    'uploaded_by' => $officer->getKey(),
                    'uploaded_by_name' => $officer->name,
                ]);
                $result['saved'][] = $final;
                if ($status === 'renamed') {
                    $result['renamed'][] = $path.' => '.$final;
                }
            } catch (\Throwable $e) {
                Log::error('KAMMA blind scan page not saved', ['path' => $path, 'error' => $e->getMessage()]);
                $result['failed'][] = $path;
            }
        }

        return $result;
    }

    /** Where a page goes inside its file folder, or null when it is refused (KLAES largeFormatPath / indexCardPath). */
    public function targetPath(string $type, ?string $relative, UploadedFile $file): ?string
    {
        $path = str_replace('\\', '/', (string) ($relative ?: $file->getClientOriginalName()));
        // No traversal, no drive or absolute prefixes.
        $segments = array_values(array_filter(explode('/', $path), fn ($s) => $s !== '' && $s !== '.' && $s !== '..' && ! str_contains($s, ':')));
        if (! $segments) {
            return null;
        }
        $name = array_pop($segments);
        $has = fn (string $folder) => collect($segments)->contains(fn ($s) => strcasecmp($s, $folder) === 0);

        return match ($type) {
            self::LF => $has('A4') ? null : 'A3/'.$name,
            self::IC => ($has('A4') || $has('A3')) ? null : EdmsFolders::INDEX_CARDS.'/'.$name,
            // A full file's own index-card folder ("Index Cards", "index_card") is stored as INDEX_CARDS.
            default => (string) preg_replace('#(^|/)INDEX[ _-]?CARDS?/#i', '$1'.EdmsFolders::INDEX_CARDS.'/', implode('/', [...$segments, $name])),
        };
    }

    public function paperOf(string $relative): string
    {
        $segments = array_map('strtoupper', explode('/', $relative));
        array_pop($segments);

        return match (true) {
            in_array(EdmsFolders::INDEX_CARDS, $segments, true) => 'IC',
            in_array('A3', $segments, true) => 'A3',
            default => 'A4',
        };
    }

    /** @return array{0: string, 1: string} [saved|renamed|skipped, final relative path] */
    private function place(Filesystem $disk, string $folder, string $path, UploadedFile $file): array
    {
        $dir = trim(dirname($path), './');
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $base = pathinfo($path, PATHINFO_FILENAME);
        $candidate = $path;
        $n = 1;
        while ($disk->exists($folder.'/'.$candidate)) {
            if ($this->same($disk, $folder.'/'.$candidate, $file)) {
                return ['skipped', $candidate];
            }
            $n++;
            $candidate = ($dir !== '' ? $dir.'/' : '').$base.' ('.$n.').'.$ext;
        }
        $disk->putFileAs($folder.($dir !== '' ? '/'.$dir : ''), $file, basename($candidate));

        return [$n > 1 ? 'renamed' : 'saved', $candidate];
    }

    private function same(Filesystem $disk, string $path, UploadedFile $file): bool
    {
        return $disk->size($path) === $file->getSize() && md5($disk->get($path)) === md5_file($file->getRealPath());
    }

    /**
     * Server Browser: one row per file folder, with its pages by paper size and how many have moved on.
     *
     * @return Collection<int, object>
     */
    public function folders(string $search = '', int $limit = 200): Collection
    {
        return BlindScan::query()
            ->where('status', '!=', 'deleted')
            ->when($search !== '', fn ($q) => $q->where('file_number', 'like', '%'.$search.'%'))
            ->select('folder', 'file_number', 'file_indexing_id')
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN paper_size = 'A4' THEN 1 ELSE 0 END) as a4, SUM(CASE WHEN paper_size = 'A3' THEN 1 ELSE 0 END) as a3")
            ->selectRaw("SUM(CASE WHEN paper_size = 'IC' THEN 1 ELSE 0 END) as ic, SUM(CASE WHEN status = 'moved' THEN 1 ELSE 0 END) as moved")
            ->selectRaw('SUM(file_size) as bytes, MAX(created_at) as last_at, MAX(uploaded_by_name) as uploaded_by_name')
            ->groupBy('folder', 'file_number', 'file_indexing_id')
            ->orderByDesc('last_at')
            ->limit($limit)
            ->get();
    }

    /** Blind Scanning Summary: folders with uploads in the last N days. */
    public function summary(int $days): Collection
    {
        $since = now()->subDays($days);
        $recent = BlindScan::query()->where('created_at', '>=', $since)->distinct()->pluck('folder');

        return $this->folders()->whereIn('folder', $recent->all())->values();
    }

    /** Upload History: one row per Migrate to Server. */
    public function history(int $limit = 50): Collection
    {
        return BlindScan::query()
            ->select('upload_batch', 'file_number', 'upload_type', 'uploaded_by_name')
            ->selectRaw('COUNT(*) as pages, MIN(created_at) as at')
            ->groupBy('upload_batch', 'file_number', 'upload_type', 'uploaded_by_name')
            ->orderByDesc('at')
            ->limit($limit)
            ->get();
    }

    /** @return array{today: int, week: int, total: int, pending: int, moved: int} */
    public function stats(): array
    {
        $live = fn () => BlindScan::query()->where('status', '!=', 'deleted');

        return [
            'today' => $live()->where('created_at', '>=', now()->startOfDay())->count(),
            'week' => $live()->where('created_at', '>=', now()->startOfWeek())->count(),
            'total' => $live()->count(),
            'pending' => $live()->where('status', BlindScan::PENDING)->count(),
            'moved' => $live()->where('status', BlindScan::MOVED)->count(),
        ];
    }

    /**
     * Removes a page still waiting in the blind-scan folder (KLAES keeps deleted scans in a trash folder, never erases):
     * the image moves to EDMS/_deleted/BLIND_SCAN/<date>/... and the row is marked deleted. Pages already taken into
     * Scan Upload stay.
     */
    public function delete(BlindScan $scan, User $officer): void
    {
        if ($scan->status !== BlindScan::PENDING) {
            throw ValidationException::withMessages(['scan' => 'This page has already moved to Scan Upload, so it stays.']);
        }
        $disk = $this->disk();
        $trash = '_deleted/'.now()->format('Ymd_His').'_'.$scan->id.'/'.$scan->path();
        DB::transaction(function () use ($scan, $disk, $trash, $officer) {
            if ($disk->exists($scan->path())) {
                $disk->move($scan->path(), $trash);
            }
            $scan->update(['status' => 'deleted', 'folder' => dirname($trash), 'relative_path' => basename($trash),
                'moved_by' => $officer->getKey(), 'moved_by_name' => $officer->name, 'moved_at' => now()]);
        });
    }
}
