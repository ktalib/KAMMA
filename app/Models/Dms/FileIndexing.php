<?php

namespace App\Models\Dms;

use App\Models\Kamma\Bill;
use App\Models\Kamma\DeskMovement;
use App\Models\Kamma\KsiPhoto;
use App\Models\Kamma\KsiReport;
use App\Models\Tracking\FileTracker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A KAMMA shadow file: KAMMA's own indexing record of a KLAES land file, copied from KLAES's
 * dbo.file_indexings when the file is matched (File Indexing Assistant / Shadow File Commissioning).
 */
class FileIndexing extends Model
{
    protected $table = 'file_indexings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date_matched' => 'date', 'is_deleted' => 'boolean', 'received_from_ps_at' => 'datetime', 'returned_to_ps_at' => 'datetime'];
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_deleted', false);
    }

    public function scannings(): HasMany
    {
        return $this->hasMany(Scanning::class, 'file_indexing_id')->where('is_deleted', false)->orderBy('display_order')->orderBy('id');
    }

    public function pageTypings(): HasMany
    {
        return $this->hasMany(PageTyping::class, 'file_indexing_id')->orderBy('page_number');
    }

    /** The file's KAMMA tracker (File Tracking), once it is logged. */
    public function tracker(): HasOne
    {
        return $this->hasOne(FileTracker::class, 'file_indexing_id');
    }

    /** The land file's route through the KAMMA desks (App\Services\Kamma\DeskFlow), oldest first. */
    public function deskMovements(): HasMany
    {
        return $this->hasMany(DeskMovement::class, 'file_indexing_id')->orderBy('acted_at')->orderBy('id');
    }

    public function ksiReports(): HasMany
    {
        return $this->hasMany(KsiReport::class, 'file_indexing_id')->latest('id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'file_indexing_id')->latest('id');
    }

    /** Geocoded site photographs (initial site visit, site assessment). */
    public function photos(): HasMany
    {
        return $this->hasMany(KsiPhoto::class, 'file_indexing_id')->latest('id');
    }

    /** Shadow files received from the PS (Shadow File Commissioning), as opposed to DMS matching of existing files. */
    public function scopeIntake(Builder $q): Builder
    {
        return $q->whereNotNull('stage');
    }
}
