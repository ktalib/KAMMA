<?php

namespace Tests\Feature;

use App\Models\Dms\BlindScan;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\Scanning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * DMS > Blind Scanning (copied from KLAES, user 2026-11-25): folders go to EDMS/BLIND_SCAN/KAMMA_Raw/<file no.> on
 * KAMMA's own storage, and Scan Upload (Upload Method = Blind scan) takes them into EDMS/SCAN_UPLOAD/KAMMA.
 */
class BlindScanTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->officer = $this->klaesUser();
    }

    private function matchFile(): FileIndexing
    {
        $id = $this->klaesFile(['registry' => 'Lands Registry']);
        $this->actingAs($this->officer)->postJson(route('dms.indexing.store'), ['klaes_file_indexing_id' => $id])->assertOk();

        return FileIndexing::query()->where('klaes_file_indexing_id', $id)->firstOrFail();
    }

    private function migrate(FileIndexing $f, string $type, array $files, array $paths)
    {
        return $this->actingAs($this->officer)->post(route('dms.blind.migrate'), [
            'file_indexing_id' => $f->id, 'upload_type' => $type, 'batch' => 'B1', 'files' => $files, 'paths' => $paths,
        ], ['Accept' => 'application/json']);
    }

    public function test_a_full_folder_is_migrated_into_kamma_raw_and_nothing_is_overwritten(): void
    {
        $f = $this->matchFile();
        $edms = Storage::disk('edms');
        $p1 = UploadedFile::fake()->image('p1.jpg', 40, 60);
        $this->migrate($f, 'full', [
            $p1, UploadedFile::fake()->image('plan.jpg', 80, 60), UploadedFile::fake()->image('card.jpg', 30, 20), UploadedFile::fake()->create('notes.docx', 5),
        ], ['A4/p1.jpg', 'A3/plan.jpg', 'Index Cards/card.jpg', 'notes.docx'])
            ->assertOk()->assertJson(['saved' => 3, 'rejected' => 1]);

        $folder = 'BLIND_SCAN/KAMMA_Raw/RES-2016-452';
        $edms->assertExists([$folder.'/A4/p1.jpg', $folder.'/A3/plan.jpg', $folder.'/INDEX_CARDS/card.jpg']);
        foreach (['UPLOAD/KAMMA', 'SCAN_UPLOAD/KAMMA', 'PAGETYPING/KAMMA', 'ARCHIVE_Doc_WARE/KAMMA'] as $stage) {
            $edms->assertExists($stage);
        }
        $this->assertSame(['A3', 'A4', 'IC'], BlindScan::query()->orderBy('paper_size')->pluck('paper_size')->all());

        // The same page again is skipped; another image under the same name is kept as "(2)".
        $this->migrate($f, 'full', [$p1], ['A4/p1.jpg'])->assertJson(['saved' => 0, 'skipped' => 1]);
        $this->migrate($f, 'full', [UploadedFile::fake()->image('p1.jpg', 41, 61)], ['A4/p1.jpg'])->assertJson(['saved' => 1, 'renamed' => 1]);
        $edms->assertExists($folder.'/A4/p1 (2).jpg');
        $this->assertSame(4, BlindScan::query()->count());
    }

    public function test_lf_and_index_card_uploads_go_to_their_own_folder_and_refuse_the_wrong_one(): void
    {
        $f = $this->matchFile();
        $this->migrate($f, 'lf', [UploadedFile::fake()->image('big.jpg'), UploadedFile::fake()->image('a4.jpg')], ['big.jpg', 'A4/a4.jpg'])
            ->assertJson(['saved' => 1, 'rejected' => 1]);
        $this->migrate($f, 'ic', [UploadedFile::fake()->image('c1.jpg'), UploadedFile::fake()->image('x.jpg')], ['c1.jpg', 'A3/x.jpg'])
            ->assertJson(['saved' => 1, 'rejected' => 1]);
        Storage::disk('edms')->assertExists(['BLIND_SCAN/KAMMA_Raw/RES-2016-452/A3/big.jpg', 'BLIND_SCAN/KAMMA_Raw/RES-2016-452/INDEX_CARDS/c1.jpg']);
        // No path tricks out of the folder.
        $this->migrate($f, 'full', [UploadedFile::fake()->image('e.jpg')], ['../../e.jpg'])->assertJson(['saved' => 1]);
        Storage::disk('edms')->assertExists('BLIND_SCAN/KAMMA_Raw/RES-2016-452/e.jpg');
    }

    public function test_the_page_lists_the_folder_and_scan_upload_takes_the_pages_into_the_file(): void
    {
        $f = $this->matchFile();
        $this->migrate($f, 'full', [UploadedFile::fake()->image('p1.jpg'), UploadedFile::fake()->image('p2.jpg'), UploadedFile::fake()->image('plan.jpg')],
            ['A4/p1.jpg', 'A4/p2.jpg', 'A3/plan.jpg'])->assertOk();

        $this->actingAs($this->officer)->get(route('dms.blind'))->assertOk()
            ->assertSee('RES-2016-452')->assertSee('Blind Scanning Summary')->assertSee('0 of 3 in Scan Upload');
        $pages = $this->actingAs($this->officer)->getJson(route('dms.blind.folder', [$f, 'pending' => 1]))->assertOk()->json('pages');
        $this->assertCount(3, $pages);
        $this->actingAs($this->officer)->get($pages[0]['url'])->assertOk();

        // Add two of them in Scan Upload: they become the file's pages in SCAN_UPLOAD/KAMMA, by paper size.
        $ids = collect($pages)->whereIn('path', ['A4/p1.jpg', 'A3/plan.jpg'])->pluck('id')->all();
        $this->actingAs($this->officer)->postJson(route('dms.scanning.blind'), ['file_indexing_id' => $f->id, 'ids' => $ids])
            ->assertOk()->assertJson(['count' => 2]);
        $scans = Scanning::query()->where('file_indexing_id', $f->id)->get();
        $this->assertSame(['A3', 'A4'], $scans->pluck('paper_size')->sort()->values()->all());
        foreach ($scans as $s) {
            $this->assertStringStartsWith('SCAN_UPLOAD/KAMMA/RES-2016-452/'.$s->paper_size.'/', $s->document_path);
            Storage::disk('edms')->assertExists($s->document_path);
        }
        $this->assertSame(2, BlindScan::query()->where('status', 'moved')->whereNotNull('scanning_id')->count());

        // Moved pages are not offered again, and stay (they cannot be deleted); a waiting page goes to the trash.
        $this->actingAs($this->officer)->postJson(route('dms.scanning.blind'), ['file_indexing_id' => $f->id, 'ids' => $ids])->assertStatus(422);
        $this->assertCount(1, $this->actingAs($this->officer)->getJson(route('dms.blind.folder', [$f, 'pending' => 1]))->json('pages'));
        $this->actingAs($this->officer)->deleteJson(route('dms.blind.destroy', $ids[0]))->assertStatus(422);
        $left = BlindScan::query()->where('status', 'pending')->firstOrFail();
        $this->actingAs($this->officer)->deleteJson(route('dms.blind.destroy', $left))->assertOk();
        Storage::disk('edms')->assertMissing('BLIND_SCAN/KAMMA_Raw/RES-2016-452/A4/p2.jpg');
        $this->assertStringStartsWith('_deleted/', $left->fresh()->path());
        Storage::disk('edms')->assertExists($left->fresh()->path());

        $this->actingAs($this->officer)->get(route('dms.blind'))->assertOk()->assertSee('2 of 2 in Scan Upload');
    }

    public function test_direct_uploads_now_go_to_scan_upload_kamma(): void
    {
        $f = $this->matchFile();
        $this->actingAs($this->officer)->post(route('dms.scanning.upload'), [
            'file_indexing_id' => $f->id, 'paper_size' => 'A3', 'documents' => [UploadedFile::fake()->image('x.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertStringStartsWith('SCAN_UPLOAD/KAMMA/RES-2016-452/A3/', Scanning::query()->sole()->document_path);
    }

    public function test_blind_scanning_needs_sign_in(): void
    {
        $this->get(route('dms.blind'))->assertRedirect(route('login'));
        $this->postJson(route('dms.blind.migrate'))->assertUnauthorized();
    }
}
