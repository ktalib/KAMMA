<?php

namespace App\Services\Dms;

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
        $folder = 'kamma/dms/'.Str::slug(str_replace('/', '-', $indexing->file_number)).'-'.$indexing->getKey();
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

    /** Removes an untyped page (KLAES: delete before page typing). Typed pages stay: retype them instead. */
    public function remove(Scanning $scan): void
    {
        if ($scan->pageTyping()->exists()) {
            throw ValidationException::withMessages(['scan' => 'This page is already typed, so it stays on the file.']);
        }
        $scan->update(['is_deleted' => true]);
    }
}
