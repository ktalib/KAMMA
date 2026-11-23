<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A stand-in for KLAES's dbo.file_indexings (only the columns this app reads), on the test-only
 * in-memory 'klaes' connection. Laravel calls setUpKlaesFiles() itself ("setUp" + trait name).
 */
trait KlaesFiles
{
    protected function setUpKlaesFiles(): void
    {
        Schema::connection('klaes')->create('file_indexings', function (Blueprint $table) {
            $table->id();
            foreach (['file_number', 'mls_file_no', 'kangis_file_no', 'new_kangis_file_no', 'temp_file_no', 'file_title',
                'land_use_type', 'plot_number', 'district', 'lga', 'location', 'plot_size', 'street_name', 'phone',
                'residence_address', 'title_status', 'title_status_type', 'registry', 'batch_no', 'shelf_location',
                'serial_no', 'tracking_id'] as $col) {
                $table->string($col)->nullable();
            }
            $table->integer('is_deleted')->nullable();
            $table->integer('is_decommissioned')->nullable();
            // The shadow-file flags (database/manual/2026_11_23_klaes_shadow_file_flags.sql).
            $table->integer('kamma_matching')->nullable();
            $table->date('kamma_date_matched')->nullable();
            $table->time('kamma_time_matched')->nullable();
        });

        // dbo.fileNumber: the same file number can sit in several columns.
        Schema::connection('klaes')->create('fileNumber', function (Blueprint $table) {
            $table->id();
            $table->string('mlsfNo')->nullable();
            $table->string('kangisFileNo')->nullable();
            $table->string('NewKANGISFileNo')->nullable();
            $table->string('FileName')->nullable();
            $table->integer('kamma_matching')->nullable();
            $table->date('kamma_date_matched')->nullable();
            $table->time('kamma_time_matched')->nullable();
        });

        // dbo.PageType / dbo.PageSubType, KLAES's page-type lookup (a few real rows).
        Schema::connection('klaes')->create('PageType', function (Blueprint $table) {
            $table->id();
            $table->string('PageType');
        });
        Schema::connection('klaes')->create('PageSubType', function (Blueprint $table) {
            $table->id();
            $table->integer('PageTypeId');
            $table->string('PageSubType');
        });
        foreach (['File Cover' => [], 'Application' => ['Certificate of Occupancy', 'Others'], 'Form' => ['Application Form'], 'Town Planning' => ['Sketch', 'Location Plan']] as $type => $subs) {
            $id = DB::connection('klaes')->table('PageType')->insertGetId(['PageType' => $type]);
            foreach ($subs as $sub) {
                DB::connection('klaes')->table('PageSubType')->insert(['PageTypeId' => $id, 'PageSubType' => $sub]);
            }
        }
    }

    protected function klaesFile(array $attributes = []): int
    {
        return DB::connection('klaes')->table('file_indexings')->insertGetId(array_merge([
            'file_number' => 'RES-2016-452',
            'file_title' => "ISMA'ILA HABU",
            'land_use_type' => 'RESIDENTIAL',
            'plot_number' => '3075',
            'district' => 'WESTERN BY- PASS',
            'lga' => 'Kumbotso',
            'location' => 'WESTERN BY- PASS, KUMBOTSO, KANO STATE',
            'plot_size' => '0.0450',
            'phone' => '08031234567',
            'residence_address' => 'NO 5 ZOO ROAD, KANO',
            'title_status' => '0',
            'is_deleted' => 0,
            'is_decommissioned' => 0,
        ], $attributes));
    }
}
