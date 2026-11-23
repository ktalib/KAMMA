<?php

namespace Tests\Feature;

use App\Models\Dms\FileIndexing;
use App\Models\Dms\PageTyping;
use App\Models\Dms\Scanning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * KAMMA DMS (the briefing's MDC exercise): Shadow File Commissioning copies the KLAES indexing record into KAMMA's
 * indexing table and flags only kamma_matching on KLAES; then Scan Upload, Page Typing (with Scan More / PageType
 * More) and Doc-WARE.
 */
class DmsTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 23)->setTime(10, 15, 0));
        $this->officer = $this->klaesUser();
    }

    private function matchFile(array $klaes = []): FileIndexing
    {
        $id = $this->klaesFile($klaes + ['registry' => 'Lands Registry', 'batch_no' => 'B-12', 'shelf_location' => 'RACK 4', 'tracking_id' => 'TRK-AB12CD34']);
        $this->actingAs($this->officer)->postJson(route('dms.indexing.store'), ['klaes_file_indexing_id' => $id, 'remarks' => 'MDC batch 1'])->assertOk();

        return FileIndexing::query()->where('klaes_file_indexing_id', $id)->firstOrFail();
    }

    private function upload(FileIndexing $f, int $n = 3): void
    {
        $files = [];
        for ($i = 1; $i <= $n; $i++) {
            $files[] = UploadedFile::fake()->image("scan_{$i}.jpg", 50, 70);
        }
        $this->actingAs($this->officer)->post(route('dms.scanning.upload'), [
            'file_indexing_id' => $f->id, 'paper_size' => 'A4', 'converted' => 1, 'documents' => $files,
        ], ['Accept' => 'application/json'])->assertOk()->assertJson(['count' => $n]);
    }

    private function type(Scanning $s, string $type, ?string $sub = null, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->officer)->postJson(route('dms.pagetyping.save', $s), ['page_type' => $type, 'page_subtype' => $sub] + $extra);
    }

    public function test_matching_copies_the_klaes_record_and_flags_only_the_kamma_columns(): void
    {
        $fnId = DB::connection('klaes')->table('fileNumber')->insertGetId(['mlsfNo' => 'RES-2016-452', 'FileName' => "ISMA'ILA HABU"]);
        $other = DB::connection('klaes')->table('fileNumber')->insertGetId(['mlsfNo' => 'RES-2016-453']);

        $f = $this->matchFile();

        $this->assertSame('RES-2016-452', $f->file_number);
        $this->assertSame("ISMA'ILA HABU", $f->file_title);
        $this->assertSame('3075', $f->plot_number);
        $this->assertSame('Kumbotso', $f->lga);
        $this->assertSame('RACK 4', $f->shelf_location);
        $this->assertSame('TRK-AB12CD34', $f->klaes_tracking_id);
        $this->assertSame('MDC batch 1', $f->remarks);
        $this->assertSame($this->officer->name, $f->matched_by_name);
        $this->assertSame('RES-2016-452', json_decode($f->klaes_record, true)['file_number']);

        $klaes = DB::connection('klaes')->table('file_indexings')->find($f->klaes_file_indexing_id);
        $this->assertSame(1, (int) $klaes->kamma_matching);
        $this->assertSame('2026-11-23', $klaes->kamma_date_matched);
        $this->assertSame("ISMA'ILA HABU", $klaes->file_title, 'KLAES file data is never changed');
        $this->assertSame(1, (int) DB::connection('klaes')->table('fileNumber')->where('id', $fnId)->value('kamma_matching'));
        $this->assertNull(DB::connection('klaes')->table('fileNumber')->where('id', $other)->value('kamma_matching'));
    }

    public function test_a_file_is_matched_once_and_must_be_live_in_klaes(): void
    {
        $f = $this->matchFile();
        $this->actingAs($this->officer)->postJson(route('dms.indexing.store'), ['klaes_file_indexing_id' => $f->klaes_file_indexing_id])
            ->assertStatus(422)->assertJsonValidationErrors('klaes_file_indexing_id');

        $gone = $this->klaesFile(['file_number' => 'RES-1990-1', 'is_decommissioned' => 1]);
        $this->actingAs($this->officer)->postJson(route('dms.indexing.store'), ['klaes_file_indexing_id' => $gone])
            ->assertStatus(422);
        $this->assertSame(1, FileIndexing::count());
    }

    public function test_lookup_marks_files_already_matched_and_klaes_preview_shows_the_record(): void
    {
        $f = $this->matchFile();
        $this->klaesFile(['file_number' => 'RES-2016-999', 'file_title' => 'AMINU BELLO']);

        $files = collect($this->actingAs($this->officer)->getJson(route('dms.indexing.lookup', ['q' => 'RES-2016']))->assertOk()->json('files'));
        $this->assertTrue($files->firstWhere('file_number', 'RES-2016-452')['matched']);
        $this->assertFalse($files->firstWhere('file_number', 'RES-2016-999')['matched']);

        $this->actingAs($this->officer)->getJson(route('dms.indexing.klaes', $f->klaes_file_indexing_id))
            ->assertOk()->assertJsonPath('file.registry', 'Lands Registry')->assertJsonPath('file.already', '23rd Nov, 2026');
    }

    public function test_indexing_page_lists_matched_files_with_sn_and_property_order(): void
    {
        $this->matchFile();
        $html = $this->actingAs($this->officer)->get(route('dms.indexing.index'))->assertOk()
            ->assertSee('File Indexing Assistant')->assertSee('RES-2016-452')->assertSee('Match Existing FileNo')->getContent();

        $order = array_map(fn ($h) => strpos($html, '>'.$h.'</th>'), ['S/N', 'Plot No.', 'District', 'LGA', 'Location']);
        $this->assertSame($order, array_values(array_filter($order)), 'every header is present');
        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order, 'S/N, Plot No., District, LGA, Location in that order');

        $this->actingAs($this->officer)->get(route('dms.indexing.index', ['search' => 'nothing-like-this']))->assertOk()->assertSee('No matched file fits that search.');
    }

    public function test_upload_then_page_typing_moves_the_file_through_the_tabs(): void
    {
        $f = $this->matchFile();
        $this->upload($f, 3);
        $scans = Scanning::query()->where('file_indexing_id', $f->id)->orderBy('display_order')->get();
        $this->assertSame([1, 2, 3], $scans->pluck('display_order')->all());
        Storage::disk('local')->assertExists($scans[0]->document_path);

        $this->actingAs($this->officer)->get(route('dms.pagetyping.index'))->assertOk()->assertSee('RES-2016-452');

        $this->type($scans[0], 'File Cover')->assertOk();
        $this->type($scans[1], 'Town Planning', 'Sketch')->assertOk()->assertJson(['code' => 'TP-1']);
        $this->actingAs($this->officer)->get(route('dms.pagetyping.index', ['tab' => 'progress']))->assertOk()->assertSee('RES-2016-452');

        $this->type($scans[2], 'Town Planning', 'Location Plan')->assertOk()->assertJson(['code' => 'TP-2', 'serial' => 2]);
        $this->actingAs($this->officer)->get(route('dms.pagetyping.index', ['tab' => 'completed']))->assertOk()->assertSee('RES-2016-452');
        $this->assertSame(3, PageTyping::count());

        // Retyping keeps one row per page; same type keeps the serial.
        $this->type($scans[2], 'Town Planning', 'Sketch')->assertOk()->assertJson(['serial' => 2]);
        $this->assertSame(3, PageTyping::count());

        // Scan More on a typed file: the new page is PageType More.
        $this->upload($f, 1);
        $new = Scanning::query()->where('file_indexing_id', $f->id)->orderByDesc('id')->first();
        $this->assertTrue($new->is_scan_more);
        $this->actingAs($this->officer)->get(route('dms.pagetyping.index', ['url' => 'ptmore', 'from' => 'dms']))->assertOk()->assertSee('RES-2016-452')->assertSee('More Pages');
        $this->actingAs($this->officer)->get(route('dms.scanning.files', ['typed' => 1]))->assertOk()->assertJsonPath('files.0.status', 'PageType More');
    }

    public function test_other_needs_the_specify_text_and_unknown_types_are_refused(): void
    {
        $f = $this->matchFile();
        $this->upload($f, 1);
        $s = Scanning::firstOrFail();

        $this->type($s, 'Other')->assertStatus(422)->assertJsonValidationErrors('page_type_others');
        $this->type($s, 'Made Up Type')->assertStatus(422)->assertJsonValidationErrors('page_type');
        $this->type($s, 'Town Planning', 'Other')->assertStatus(422)->assertJsonValidationErrors('page_subtype_others');
        $this->type($s, 'Other', null, ['page_type_others' => '  Neighbour   consent letter '])->assertOk();
        $this->assertSame('Neighbour consent letter', PageTyping::firstOrFail()->typeLabel());
    }

    public function test_untyped_pages_can_be_removed_typed_pages_cannot(): void
    {
        $f = $this->matchFile();
        $this->upload($f, 2);
        [$a, $b] = Scanning::query()->orderBy('id')->get()->all();
        $this->type($a, 'File Cover')->assertOk();

        $this->actingAs($this->officer)->deleteJson(route('dms.scans.destroy', $a))->assertStatus(422);
        $this->actingAs($this->officer)->deleteJson(route('dms.scans.destroy', $b))->assertOk();
        $this->assertTrue($b->fresh()->is_deleted);
        $this->actingAs($this->officer)->get(route('dms.scans.image', $b))->assertNotFound();
    }

    public function test_doc_ware_lists_typed_files_and_opens_the_viewer(): void
    {
        $f = $this->matchFile();
        $untyped = $this->matchFile(['file_number' => 'RES-2017-10', 'file_title' => 'BELLO SANI']);
        $this->upload($f, 2);
        $this->upload($untyped, 1);
        foreach (Scanning::query()->where('file_indexing_id', $f->id)->get() as $s) {
            $this->type($s, 'Town Planning', 'Location Plan')->assertOk();
        }

        $this->actingAs($this->officer)->get(route('archive.index'))->assertOk()
            ->assertSee('File Digital Archive')->assertSee('RES-2016-452')->assertDontSee('RES-2017-10');
        $this->actingAs($this->officer)->get(route('archive.index', ['q' => 'kumbotso', 'page_type' => 'Town Planning']))->assertOk()->assertSee('RES-2016-452');
        $this->actingAs($this->officer)->get(route('archive.show', $f))->assertOk()->assertSee('Town Planning')->assertSee('TP-2');
        $this->actingAs($this->officer)->get(route('archive.show', FileIndexing::query()->where('file_number', 'RES-2017-10')->first()))->assertOk()->assertSee('Not typed yet');
    }

    public function test_scan_upload_screens_and_sidebar_links(): void
    {
        $f = $this->matchFile();
        $this->actingAs($this->officer)->get(route('dms.scanning.index', ['file' => $f->id]))->assertOk()->assertSee('Document Upload')->assertSee('RES-2016-452');
        $this->actingAs($this->officer)->get(route('dms.scanning.index', ['url' => 'scmore', 'from' => 'dms']))->assertOk()->assertSee('Scan More');
        $this->actingAs($this->officer)->get(route('dms.pagetyping.index'))->assertOk()
            ->assertSee(route('dms.indexing.index'), false)->assertSee(route('archive.index'), false);
    }

    public function test_dms_needs_sign_in(): void
    {
        $this->get(route('dms.indexing.index'))->assertRedirect(route('login'));
        $this->postJson(route('dms.indexing.store'), ['klaes_file_indexing_id' => 1])->assertUnauthorized();
        $this->get(route('archive.index'))->assertRedirect(route('login'));
    }
}
