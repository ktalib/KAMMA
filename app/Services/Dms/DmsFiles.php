<?php

namespace App\Services\Dms;

use App\Models\Dms\FileIndexing;
use App\Models\Dms\PageTyping;
use App\Models\Dms\Scanning;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Lists and figures for the DMS screens (File Indexing Assistant, Upload Indexed Files, PageTyping, Doc-WARE).
 * A file's DMS state follows KLAES: Indexed → Scanned (pending page typing) → In progress → Page typed, and back to
 * PageType More when Scan More adds pages to a typed file.
 */
class DmsFiles
{
    public const PT_TABS = ['pending' => 'Pending Page Typing', 'progress' => 'In Progress', 'completed' => 'Completed', 'more' => 'PageType More'];

    /** Indexed files with their page counts. */
    public function query(): Builder
    {
        return FileIndexing::query()->live()
            ->withCount([
                'scannings as pages_count',
                'pageTypings as typed_count',
                'scannings as untyped_count' => fn (Builder $q) => $q->whereDoesntHave('pageTyping'),
                'scannings as more_count' => fn (Builder $q) => $q->where('is_scan_more', true)->whereDoesntHave('pageTyping'),
            ]);
    }

    public function search(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $q;
        }
        $like = '%'.$this->escape($term).'%';

        // Case-insensitive on SQL Server's default collation; lower() keeps SQLite (tests) the same.
        return $q->where(fn (Builder $w) => $w
            ->whereRaw('lower(file_number) like ?', [mb_strtolower($like)])
            ->orWhereRaw('lower(file_title) like ?', [mb_strtolower($like)])
            ->orWhereRaw('lower(plot_number) like ?', [mb_strtolower($like)])
            ->orWhereRaw('lower(district) like ?', [mb_strtolower($like)])
            ->orWhereRaw('lower(lga) like ?', [mb_strtolower($like)])
            ->orWhereRaw('lower(location) like ?', [mb_strtolower($like)]));
    }

    public function status(FileIndexing $f): string
    {
        return match (true) {
            ($f->pages_count ?? 0) === 0 => 'Indexed',
            ($f->more_count ?? 0) > 0 => 'PageType More',
            ($f->typed_count ?? 0) === 0 => 'Scanned',
            ($f->untyped_count ?? 0) > 0 => 'In Progress',
            default => 'Page Typed',
        };
    }

    /** @return array<string, int> */
    public function indexingStats(): array
    {
        $base = fn () => FileIndexing::query()->live();

        return [
            'today' => $base()->whereDate('date_matched', today())->count(),
            'month' => $base()->whereBetween('date_matched', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->count(),
            'total' => $base()->count(),
            'scanned' => $base()->whereHas('scannings')->count(),
            'typed' => $base()->whereHas('pageTypings')->whereDoesntHave('scannings', fn (Builder $q) => $q->whereDoesntHave('pageTyping'))->count(),
        ];
    }

    /** @return array<string, int> */
    public function scanStats(): array
    {
        $pages = fn () => Scanning::query()->where('is_deleted', false)->whereHas('indexing', fn (Builder $q) => $q->live());

        return [
            'today' => $pages()->whereDate('created_at', today())->count(),
            'pending' => FileIndexing::query()->live()->whereHas('scannings', fn (Builder $q) => $q->whereDoesntHave('pageTyping'))->count(),
            'files' => FileIndexing::query()->live()->whereHas('scannings')->count(),
            'pages' => $pages()->count(),
        ];
    }

    public function ptTab(Builder $q, string $tab): Builder
    {
        $untyped = fn (Builder $s) => $s->whereDoesntHave('pageTyping');

        return match ($tab) {
            'progress' => $q->whereHas('pageTypings')->whereHas('scannings', fn (Builder $s) => $untyped($s)->where('is_scan_more', false)),
            'completed' => $q->whereHas('scannings')->whereDoesntHave('scannings', $untyped),
            'more' => $q->whereHas('scannings', fn (Builder $s) => $untyped($s)->where('is_scan_more', true)),
            default => $q->whereHas('scannings')->whereDoesntHave('pageTypings'),
        };
    }

    /** @return array<string, int> */
    public function ptStats(): array
    {
        $out = [];
        foreach (array_keys(self::PT_TABS) as $tab) {
            $out[$tab] = $this->ptTab(FileIndexing::query()->live(), $tab)->count();
        }

        return $out;
    }

    /** @return array<string, int|float> */
    public function archiveStats(): array
    {
        $typed = $this->ptTab(FileIndexing::query()->live(), 'completed');
        $bytes = (int) Scanning::query()->where('is_deleted', false)->whereHas('pageTyping')->sum('file_size');

        return [
            'files' => (clone $typed)->count(),
            'recent' => (clone $typed)->whereHas('pageTypings', fn (Builder $q) => $q->where('created_at', '>=', now()->subDays(30)))->count(),
            'pages' => PageTyping::query()->whereHas('indexing', fn (Builder $q) => $q->live())->count(),
            'bytes' => $bytes,
        ];
    }

    /** Doc-WARE: every file with typed pages (a file with new untyped pages stays visible). */
    public function archive(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $q = $this->search($this->query()->whereHas('pageTypings'), $filters['q'] ?? null);

        if (! empty($filters['land_use'])) {
            $q->where('land_use_type', $filters['land_use']);
        }
        if (! empty($filters['lga'])) {
            $q->where('lga', $filters['lga']);
        }
        if (! empty($filters['page_type'])) {
            $q->whereHas('pageTypings', fn (Builder $p) => $p->where('page_type', $filters['page_type']));
        }
        if (! empty($filters['year'])) {
            $q->whereYear('date_matched', (int) $filters['year']);
        }

        match ($filters['sort'] ?? 'recent') {
            'file' => $q->orderBy('file_number'),
            'pages' => $q->orderByDesc('pages_count'),
            default => $q->orderByDesc('date_matched')->orderByDesc('id'),
        };

        return $q->paginate($perPage)->withQueryString();
    }

    /** The cover of a file: its first "File Cover" page, else its first page. */
    public function cover(FileIndexing $f): ?Scanning
    {
        $typed = PageTyping::query()->where('file_indexing_id', $f->getKey())
            ->whereRaw("lower(page_type) like '%cover%'")->orderBy('page_number')->first();

        return $typed?->scanning ?? Scanning::query()->where('file_indexing_id', $f->getKey())->where('is_deleted', false)
            ->orderBy('display_order')->orderBy('id')->first();
    }

    private function escape(string $v): string
    {
        if (FileIndexing::query()->getConnection()->getDriverName() !== 'sqlsrv') {
            return $v;
        }

        return str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $v);
    }
}
