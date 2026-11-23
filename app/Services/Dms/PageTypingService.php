<?php

namespace App\Services\Dms;

use App\Models\Dms\PageTyping;
use App\Models\Dms\Scanning;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Page Typing (KLAES PageTypingController::saveSingle): each scanned page gets a page type, a subtype and a serial
 * number that runs per page type within the file (KLAES next-serial-for-page-type). Retyping a page keeps its
 * serial unless the type changes.
 */
class PageTypingService
{
    public function __construct(private PageTypes $types)
    {
    }

    /** @param array{page_type: string, page_type_others?: ?string, page_subtype?: ?string, page_subtype_others?: ?string} $data */
    public function save(Scanning $scan, array $data, User $officer): PageTyping
    {
        if ($scan->is_deleted) {
            throw ValidationException::withMessages(['scan' => 'That page has been removed.']);
        }

        $type = trim($data['page_type']);
        $sub = isset($data['page_subtype']) ? trim((string) $data['page_subtype']) : null;
        $typeOther = $type === 'Other' ? $this->normalise($data['page_type_others'] ?? null) : null;
        $subOther = $sub === 'Other' ? $this->normalise($data['page_subtype_others'] ?? null) : null;

        if (! $this->types->valid($type, $sub)) {
            throw ValidationException::withMessages(['page_type' => 'Pick a page type and subtype from the list, or Other.']);
        }
        if ($type === 'Other' && $typeOther === null) {
            throw ValidationException::withMessages(['page_type_others' => 'Specify the page type.']);
        }
        if ($sub === 'Other' && $subOther === null) {
            throw ValidationException::withMessages(['page_subtype_others' => 'Specify the subtype.']);
        }

        return DB::transaction(function () use ($scan, $type, $sub, $typeOther, $subOther, $officer) {
            $existing = PageTyping::query()->where('scanning_id', $scan->getKey())->lockForUpdate()->first();
            $label = $typeOther ?? $type;

            $serial = $existing && $existing->typeLabel() === $label
                ? $existing->serial_number
                : 1 + (int) PageTyping::query()->where('file_indexing_id', $scan->file_indexing_id)
                    ->where(fn ($q) => $type === 'Other'
                        ? $q->where('page_type', 'Other')->where('page_type_others', $typeOther)
                        : $q->where('page_type', $type))
                    ->max('serial_number');

            $values = [
                'file_indexing_id' => $scan->file_indexing_id,
                'page_number' => $this->pageNumber($scan),
                'page_type' => $type,
                'page_type_others' => $typeOther,
                'page_subtype' => $sub === '' ? null : $sub,
                'page_subtype_others' => $subOther,
                'serial_number' => $serial,
                'page_code' => $this->code($label, $serial),
                'typed_by' => $officer->getKey(),
                'typed_by_name' => $officer->name,
            ];

            return $existing ? tap($existing)->update($values) : PageTyping::query()->create($values + ['scanning_id' => $scan->getKey()]);
        });
    }

    /** The page's place in the file, in upload order. */
    private function pageNumber(Scanning $scan): int
    {
        return 1 + Scanning::query()->where('file_indexing_id', $scan->file_indexing_id)->where('is_deleted', false)
            ->where(fn ($q) => $q->where('display_order', '<', $scan->display_order)
                ->orWhere(fn ($q) => $q->where('display_order', $scan->display_order)->where('id', '<', $scan->getKey())))
            ->count();
    }

    /** e.g. "Building Plan" serial 3 → BP-3; "Application" → APP-3 (KLAES page codes are type initials + serial). */
    private function code(string $type, int $serial): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', strtoupper($type), -1, PREG_SPLIT_NO_EMPTY);
        $prefix = count($words) > 1 ? implode('', array_map(fn ($w) => $w[0], $words)) : substr($words[0] ?? 'PG', 0, 3);

        return $prefix.'-'.$serial;
    }

    private function normalise(?string $v): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string) $v));

        return $v === '' ? null : mb_substr($v, 0, 200);
    }
}
