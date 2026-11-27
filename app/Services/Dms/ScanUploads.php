<?php

namespace App\Services\Dms;

use App\Models\Dms\BlindScan;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\Scanning;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Upload Indexed Files (KLAES Scan Uploads): scanned pages go onto an indexed KAMMA file. The browser turns a PDF
 * into one JPG per page first (KLAES "Convert PDF files to images (client-side)"), so each page can be typed.
 * Pages added after the file was page typed are Scan More pages and show under PageType More.
 */
class ScanUploads
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<Scanning>
     */
    public function store(FileIndexing $indexing, array $files, string $paperSize, bool $converted, User $officer): array
    {
        if ($indexing->is_deleted) {
            throw ValidationException::withMessages(['file_indexing_id' => 'That indexed file has been removed.']);
        }

        $disk = Storage::disk(config('kamma.dms_disk'));
        // EDMS/SCAN_UPLOAD/KAMMA/<file no.>/<A4|A3>, as KLAES's SCAN_UPLOAD/Lands_Registry (user 2026-11-25).
        EdmsFolders::ensure($disk);
        $folder = EdmsFolders::fileFolder(EdmsFolders::SCAN_UPLOAD, $indexing->file_number).'/'.$paperSize;
        $batch = now()->format('YmdHis').'-'.Str::lower(Str::random(4));
        $stored = [];

        try {
            return DB::transaction(function () use ($indexing, $files, $paperSize, $converted, $officer, $disk, $folder, $batch, &$stored) {
                $scanMore = $indexing->pageTypings()->exists();
                $order = (int) Scanning::query()->where('file_indexing_id', $indexing->getKey())->max('display_order');
                $rows = [];

                foreach ($files as $file) {
                    $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
                    $path = $file->storeAs($folder, Str::uuid().'.'.$ext, ['disk' => config('kamma.dms_disk')]);
                    $stored[] = $path;

                    $rows[] = Scanning::query()->create([
                        'file_indexing_id' => $indexing->getKey(),
                        'document_path' => $path,
                        'original_filename' => mb_substr($file->getClientOriginalName(), 0, 300),
                        'mime' => $file->getMimeType(),
                        'file_size' => (int) $file->getSize(),
                        'paper_size' => $paperSize,
                        'display_order' => ++$order,
                        'is_pdf_converted' => $converted,
                        'upload_batch' => $batch,
                        'is_scan_more' => $scanMore,
                        'uploaded_by' => $officer->getKey(),
                        'uploaded_by_name' => $officer->name,
                    ]);
                }

                return $rows;
            });
        } catch (\Throwable $e) {
            foreach ($stored as $path) {
                $disk->delete($path);
            }
            throw $e;
        }
    }

    /**
     * Upload Method = Blind scan (KLAES Scan Upload "Blind Scan Documents" > Add): the picked pages of the file's
     * EDMS/BLIND_SCAN/KAMMA_Raw folder are copied into EDMS/SCAN_UPLOAD/KAMMA/<file no.>/<A4|A3> and become the file's
     * scanned pages; their blind-scan rows are marked moved. The blind-scan copy stays where it is (KLAES keeps the raw
     * folder). Index cards go in as A4 pages.
     *
     * @param  list<int>  $ids  kamma_blind_scans ids
     * @return list<Scanning>
     */
    public function fromBlindScans(FileIndexing $indexing, array $ids, User $officer): array
    {
        if ($indexing->is_deleted) {
            throw ValidationException::withMessages(['file_indexing_id' => 'That indexed file has been removed.']);
        }
        $scans = BlindScan::query()->where('file_indexing_id', $indexing->getKey())->where('status', BlindScan::PENDING)
            ->whereIn('id', $ids)->orderBy('relative_path')->get();
        if ($scans->isEmpty()) {
            throw ValidationException::withMessages(['ids' => 'Pick at least one blind-scan page that has not been added yet.']);
        }

        $disk = Storage::disk(config('kamma.dms_disk'));
        EdmsFolders::ensure($disk);
        $base = EdmsFolders::fileFolder(EdmsFolders::SCAN_UPLOAD, $indexing->file_number);
        $batch = now()->format('YmdHis').'-'.Str::lower(Str::random(4));
        $copied = [];

        try {
            return DB::transaction(function () use ($indexing, $scans, $officer, $disk, $base, $batch, &$copied) {
                $scanMore = $indexing->pageTypings()->exists();
                $order = (int) Scanning::query()->where('file_indexing_id', $indexing->getKey())->max('display_order');
                $rows = [];

                foreach ($scans as $blind) {
                    abort_unless($disk->exists($blind->path()), 422, "{$blind->relative_path} is missing in the blind-scan folder.");
                    $paper = $blind->paper_size === 'A3' ? 'A3' : 'A4';
                    $ext = strtolower(pathinfo($blind->relative_path, PATHINFO_EXTENSION)) ?: 'jpg';
                    $path = $base.'/'.$paper.'/'.Str::uuid().'.'.$ext;
                    $disk->copy($blind->path(), $path);
                    $copied[] = $path;

                    $row = Scanning::query()->create([
                        'file_indexing_id' => $indexing->getKey(),
                        'document_path' => $path,
                        'original_filename' => mb_substr($blind->original_filename, 0, 300),
                        'mime' => $blind->mime,
                        'file_size' => (int) $blind->file_size,
                        'paper_size' => $paper,
                        'display_order' => ++$order,
                        'is_pdf_converted' => false,
                        'upload_batch' => $batch,
                        'is_scan_more' => $scanMore,
                        'uploaded_by' => $officer->getKey(),
                        'uploaded_by_name' => $officer->name,
                    ]);
                    $blind->update(['status' => BlindScan::MOVED, 'scanning_id' => $row->id, 'moved_at' => now(),
                        'moved_by' => $officer->getKey(), 'moved_by_name' => $officer->name]);
                    $rows[] = $row;
                }

                return $rows;
            });
        } catch (\Throwable $e) {
            foreach ($copied as $path) {
                $disk->delete($path);
            }
            throw $e;
        }
    }

    /** Removes an untyped page (KLAES: delete before page typing). Typed pages stay: retype them instead. */
    public function remove(Scanning $scan): void
    {
        if ($scan->pageTyping()->exists()) {
            throw ValidationException::withMessages(['scan' => 'This page is already typed, so it stays on the file.']);
        }
        $scan->update(['is_deleted' => true]);
    }
}
