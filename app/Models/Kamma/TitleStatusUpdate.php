<?php

namespace App\Models\Kamma;

use Illuminate\Database\Eloquent\Model;

/** A KAMMA Title Status Update record (KLAES title_status_applications shape). */
class TitleStatusUpdate extends Model
{
    protected $table = 'title_status_updates';

    protected $guarded = ['id'];
}
