<?php

namespace App\Services\Dms;

use App\Models\Dms\FileIndexing;
use App\Models\Dms\PageTyping;
use App\Models\Dms\Scanning;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Doc-WARE actions, copied from KLAES's File Digital Archive (user 2026-11-25): Delete File (Super Admin), and in the
 * viewer's Edit Mode Delete page, Rotate (saved), Replace and QC Pass / Fail. Edit Type reuses Page Typing.
 *
 * KLAES never erases: what is removed or replaced moves to EDMS/_deleted/<date>_<what>/ with a manifest.json of the
 * rows (KLAES storage/app/edms-deleted). The indexing record always stays.
 */
class DocWareActions
{
    /** Images GD can rotate and save in place. */
    private const ROTATABLE = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    public function disk(): Filesystem
    {
        return Storage::disk(config('kamma.dms_disk'));
    }

    /**
     * Delete File (KLAES Doc-WARE card menu, Super Admin only): every scanned page of the file and its typing are taken
     * off the file and moved to the trash with a manifest. The shadow file (indexing record) is kept.
     *
     * @return array{pages: int, typed: int, trash: string}
     */
    public function deleteFile(FileIndexing $indexing, string $reason, User $officer): array
    {
        $scans = $indexing->scannings()->where('is_deleted', false)->get();
        if ($scans->isEmpty()) {
            throw ValidationException::withMessages(['file' => 'This file has no documents in Doc-WARE.']);
        }
        $typings = PageTyping::query()->whereIn('scanning_id', $scans->pluck('id'))->get();
        $trash = '_deleted/'.now()->format('Ymd_His').'_file_'.$indexing->getKey().'_'.EdmsFolders::folderName($indexing->file_number);

        $this->trash($trash, $scans->all(), [
            'action' => 'Delete File', 'reason' => $reason, 'file_indexing' => $indexing->only(['id', 'file_number', 'file_title', 'klaes_file_indexing_id']),
            'page_typings' => $typings->toArray(),
        ], $officer, function () use ($scans, $typings) {
            PageTyping::query()->whereIn('id', $typings->pluck('id'))->delete();
            foreach ($scans as $s) {
                $s->update(['is_deleted' => true]);
            }
        });

        return ['pages' => $scans->count(), 'typed' => $typings->count(), 'trash' => $trash];
    }

    /** Delete page (viewer, Edit Mode): one page and its typing go to the trash. */
    public function deletePage(Scanning $scan, User $officer): void
    {
        abort_if($scan->is_deleted, 404);
        $typing = $scan->pageTyping()->first();
        $trash = '_deleted/'.now()->format('Ymd_His').'_page_'.$scan->getKey();

        $this->trash($trash, [$scan], ['action' => 'Delete page', 'page_typings' => $typing ? [$typing->toArray()] : []], $officer, function () use ($scan, $typing) {
            $typing?->delete();
            $scan->update(['is_deleted' => true]);
        });
    }

    /** QC Pass / Fail on a typed page (KLAES Quality Control; same column as DMS > PT Quality Control). */
    public function qc(Scanning $scan, string $result): PageTyping
    {
        $typing = $scan->pageTyping()->first();
        if ($typing === null) {
            throw ValidationException::withMessages(['qc' => 'Type this page first; quality control is for typed pages.']);
        }
        $typing->update(['qc_status' => $result === 'pass' ? 'passed' : 'failed']);

        return $typing;
    }

    /** Rotate and save (KLAES Rotate left / right): the original is kept in the trash, the turned page replaces it. */
    public function rotate(Scanning $scan, int $degrees, User $officer): void
    {
        $degrees = (($degrees % 360) + 360) % 360;
        if (! in_array($degrees, [90, 180, 270], true)) {
            throw ValidationException::withMessages(['degrees' => 'Turn the page by 90, 180 or 270 degrees.']);
        }
        $ext = strtolower(pathinfo($scan->document_path, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ROTATABLE, true) || str_contains((string) $scan->mime, 'pdf')) {
            throw ValidationException::withMessages(['degrees' => 'Only JPG, PNG, GIF, WebP or BMP pages can be turned and saved.']);
        }
        $disk = $this->disk();
        abort_unless($disk->exists($scan->document_path), 404, 'The scanned page is missing on disk.');

        $image = @imagecreatefromstring($disk->get($scan->document_path));
        if ($image === false) {
            throw ValidationException::withMessages(['degrees' => 'This image could not be read, so it was not turned.']);
        }
        // GD turns anticlockwise; the viewer's degrees are clockwise.
        $turned = imagerotate($image, 360 - $degrees, 0);
        ob_start();
        match ($ext) {
            'png' => imagepng($turned),
            'gif' => imagegif($turned),
            'webp' => imagewebp($turned, null, 90),
            'bmp' => imagebmp($turned),
            default => imagejpeg($turned, null, 92),
        };
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        imagedestroy($turned);

        $trash = '_deleted/'.now()->format('Ymd_His').'_rotate_'.$scan->getKey();
        $this->keepOriginal($trash, $scan, ['action' => 'Rotate '.$degrees], $officer);
        $disk->put($scan->document_path, $bytes);
        $scan->update(['file_size' => strlen($bytes)]);
    }

    /** Replace with a new image (KLAES Replace): the old page goes to the trash; the typing stays on the page. */
    public function replace(Scanning $scan, UploadedFile $file, User $officer): void
    {
        abort_if($scan->is_deleted, 404);
        $disk = $this->disk();
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $dir = trim(dirname($scan->document_path), './');
        $new = $file->storeAs($dir, Str::uuid().'.'.$ext, ['disk' => config('kamma.dms_disk')]);

        try {
            $trash = '_deleted/'.now()->format('Ymd_His').'_replace_'.$scan->getKey();
            $this->trash($trash, [$scan], ['action' => 'Replace page', 'new_path' => $new], $officer, function () use ($scan, $file, $new) {
                $scan->update(['document_path' => $new, 'original_filename' => mb_substr($file->getClientOriginalName(), 0, 300),
                    'mime' => $file->getMimeType(), 'file_size' => (int) $file->getSize(), 'is_pdf_converted' => false]);
            });
        } catch (\Throwable $e) {
            $disk->delete($new);
            throw $e;
        }
    }

    /**
     * Moves the pages' images into $trash/files/<their path> with $trash/manifest.json, then runs $change in a transaction.
     * If the change fails, the images go back.
     *
     * @param  list<Scanning>  $scans
     */
    private function trash(string $trash, array $scans, array $manifest, User $officer, callable $change): void
    {
        $disk = $this->disk();
        $moved = [];
        foreach ($scans as $s) {
            if ($disk->exists($s->document_path)) {
                $disk->move($s->document_path, $trash.'/files/'.$s->document_path);
                $moved[$s->document_path] = $trash.'/files/'.$s->document_path;
            }
        }
        $disk->put($trash.'/manifest.json', json_encode($manifest + [
            'at' => now()->toDateTimeString(), 'by' => ['id' => $officer->getKey(), 'name' => $officer->name],
            'scannings' => array_map(fn (Scanning $s) => $s->toArray(), $scans),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        try {
            DB::transaction($change);
        } catch (\Throwable $e) {
            foreach ($moved as $from => $to) {
                $disk->move($to, $from);
            }
            throw $e;
        }
    }

    /** A copy of the page as it was, before it is changed in place. */
    private function keepOriginal(string $trash, Scanning $scan, array $manifest, User $officer): void
    {
        $disk = $this->disk();
        $disk->copy($scan->document_path, $trash.'/files/'.$scan->document_path);
        $disk->put($trash.'/manifest.json', json_encode($manifest + [
            'at' => now()->toDateTimeString(), 'by' => ['id' => $officer->getKey(), 'name' => $officer->name], 'scannings' => [$scan->toArray()],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
