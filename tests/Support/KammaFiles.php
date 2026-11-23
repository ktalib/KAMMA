<?php

namespace Tests\Support;

use App\Models\Dms\FileIndexing;

/**
 * KAMMA shadow files for the tests (use with KlaesFiles + KlaesUsers and an $officer property).
 *
 *  - intakeFile(): Shadow File Commissioning from the KAMMA menu: the PS sends the land file, KAMMA commissions the
 *    shadow file (tracking starts in the Registry) and routes it to the MD KAMMA.
 *  - untrackedFile(): a shadow file with no KAMMA tracker yet (e.g. matched before tracking existed).
 */
trait KammaFiles
{
    protected function intakeFile(array $klaes = [], string $type = 'conversion'): FileIndexing
    {
        $id = $this->klaesFile($klaes);
        $this->actingAs($this->officer)->post(route('kamma.shadow-files.store'), [
            'klaes_file_indexing_id' => $id, 'application_type' => $type, 'remarks' => 'From the PS',
        ])->assertSessionHasNoErrors();

        return FileIndexing::query()->where('klaes_file_indexing_id', $id)->sole();
    }

    protected function untrackedFile(string $fileNumber = 'RES-2017-10'): FileIndexing
    {
        return FileIndexing::query()->create([
            'klaes_file_indexing_id' => 9000 + FileIndexing::query()->count(), 'file_number' => $fileNumber, 'file_title' => 'BELLO SANI',
            'plot_number' => '12', 'district' => 'GWALE', 'lga' => 'Gwale', 'location' => 'GWALE, KANO STATE', 'land_use_type' => 'RESIDENTIAL',
            'date_matched' => now()->toDateString(), 'time_matched' => now()->format('H:i:s'), 'matched_by_name' => 'Amina Bello',
        ]);
    }
}
