<?php

namespace Tests\Feature;

use App\Models\Dms\FileIndexing;
use App\Models\Dms\PrintLabelBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\KammaFiles;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * DMS > Print Files Label, copied from KLAES (page + JS), answered from KAMMA shadow files: Select Files, Batch Mode by
 * batch number, Manual Override, rack/shelf written to the shadow file, Generated Batches, reprint, mark printed, delete.
 */
class PrintLabelTest extends TestCase
{
    use KammaFiles, KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 25));
        $this->officer = $this->klaesUser();
    }

    private function file(string $no, string $batch = 'B7', string $serial = '1'): FileIndexing
    {
        $f = $this->untrackedFile($no);
        $f->update(['batch_no' => $batch, 'serial_no' => $serial]);

        return $f;
    }

    private function api(string $path): string
    {
        return '/dms/print-labels/api/'.$path;
    }

    public function test_the_page_is_the_klaes_print_file_labels_screen(): void
    {
        $this->actingAs($this->officer)->get(route('dms.labels'))->assertOk()
            ->assertSee('Print File Labels')->assertSee('Select Files')->assertSee('Generated Batches')
            ->assertSee('Label Settings')->assertSee('Preview & Print', false)->assertSee('Batch Mode')
            ->assertSee('Manual Registry Override')->assertSee(url('dms/print-labels/api'))
            ->assertDontSee('KANGIS PFL');
        $this->get(route('dms.labels.print-template'))->assertOk();
    }

    public function test_manual_selection_creates_a_batch_and_the_files_leave_the_list(): void
    {
        $a = $this->file('RES-2016-1', 'B7', '2');
        $b = $this->file('RES-2016-2', 'B7', '1');
        $this->actingAs($this->officer);

        $this->getJson($this->api('files'))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.file_number', 'RES-2016-2');
        $this->getJson($this->api('files?search=2016-1'))->assertOk()->assertJsonCount(1, 'data');

        $r = $this->postJson($this->api('batch'), ['source' => 'file_indexings', 'label_format' => '30-in-1', 'orientation' => 'portrait', 'file_ids' => [$a->id, $b->id]])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.file_count', 2)
            ->assertJsonPath('data.label_items.0.file_number', 'RES-2016-1')
            ->assertJsonPath('data.label_items.0.qr_value', 'RES-2016-1');
        $this->assertSame('PLB-KMA-2026-11-001', $r->json('data.batch_number'));

        $this->getJson($this->api('files'))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson($this->api('statistics'))->assertJsonPath('data.generated_batches', 1)->assertJsonPath('data.available_files', 0);
        $this->postJson($this->api('batch'), ['source' => 'file_indexings', 'label_format' => '30-in-1', 'orientation' => 'portrait', 'file_ids' => [999]])
            ->assertStatus(422);
    }

    public function test_batch_mode_loads_by_batch_number_and_assigns_the_shelf(): void
    {
        $files = collect([3, 1, 2])->map(fn ($s) => $this->file("RES-2016-1{$s}", 'B7', (string) $s));
        $this->file('COM-2020-5', 'B8', '1');
        $this->actingAs($this->officer);

        $p = $this->getJson($this->api('grouping/preview?registry_batch_no=B7&start=1&limit=100&per_batch_limit=100'))->assertOk();
        $this->assertSame(['RES-2016-11', 'RES-2016-12', 'RES-2016-13'], array_column($p->json('data.records'), 'file_number'));
        $this->getJson($this->api('grouping/preview?registry_batch_no=B7&year=2020'))->assertOk()->assertJsonCount(0, 'data.records');

        $records = collect($p->json('data.records'))->take(2)->map(fn ($r) => ['id' => $r['id']])->all();
        $this->postJson($this->api('batch'), [
            'source' => 'grouping', 'label_format' => '30-in-1', 'orientation' => 'portrait', 'registry_batch_no' => 'B7',
            'rack_primary' => 'C', 'rack_secondary' => null, 'shelf' => 4, 'records' => $records,
        ])->assertOk()->assertJsonPath('data.label_items.0.shelf_location', 'C4')->assertJsonPath('data.registry_progress.printed', 2);

        $this->assertSame(['C4', 'C4', null], FileIndexing::query()->whereIn('file_number', ['RES-2016-11', 'RES-2016-12', 'RES-2016-13'])->orderBy('file_number')->pluck('shelf_location')->all());
        $this->getJson($this->api('rack-label/status?full_label=c4'))->assertOk()->assertJsonPath('data.counter', 2)->assertJsonPath('data.assigned_batch', 'B7');

        // Next load continues after what is already labelled.
        $next = $this->getJson($this->api('grouping/preview?registry_batch_no=B7&start=1'))->assertOk();
        $this->assertSame(['RES-2016-13'], array_column($next->json('data.records'), 'file_number'));
        $this->assertSame(3, $next->json('data.registry_progress.next_start'));
    }

    public function test_manual_override_finds_typed_file_numbers_and_sub_groups_get_their_own_shelf(): void
    {
        $a = $this->file('RES-2016-21');
        $b = $this->file('RES-2016-22');
        $this->actingAs($this->officer);

        $r = $this->postJson($this->api('override/lookup'), ['file_numbers' => ['res-2016-22', 'RES-2016-21', 'RES-2099-1']])->assertOk();
        $this->assertSame(['RES-2016-22', 'RES-2016-21'], array_column($r->json('data'), 'file_number'));
        $this->assertSame(['RES-2099-1'], $r->json('meta.not_found'));

        $this->postJson($this->api('batch'), [
            'source' => 'grouping', 'label_format' => '30-in-1', 'orientation' => 'portrait', 'registry_batch_no' => 'OVERRIDE-1',
            'rack_primary' => 'A', 'shelf' => 1, 'records' => [['id' => $b->id], ['id' => $a->id]],
            'sub_groups' => [
                ['record_ids' => [$b->id], 'rack_primary' => 'A', 'shelf' => 1],
                ['record_ids' => [$a->id], 'rack_primary' => 'B', 'rack_secondary' => 'C', 'shelf' => 9],
            ],
        ])->assertOk();

        $this->assertSame('A1', $b->refresh()->shelf_location);
        $this->assertSame('BC9', $a->refresh()->shelf_location);
    }

    public function test_generated_batches_reprint_mark_printed_and_delete(): void
    {
        $a = $this->file('RES-2016-31');
        $this->actingAs($this->officer);
        $id = $this->postJson($this->api('batch'), ['source' => 'file_indexings', 'label_format' => '30-in-1', 'orientation' => 'portrait', 'file_ids' => [$a->id]])->json('data.batch_id');

        $this->getJson($this->api('batches'))->assertOk()->assertJsonPath('data.0.batch_items_count', 1)
            ->assertJsonPath('data.0.file_year', '2016')->assertJsonPath('data.0.creator.name', $this->officer->name);
        $this->getJson($this->api("batch/{$id}"))->assertOk()->assertJsonPath('data.batch_items.0.file_number', 'RES-2016-31');
        $this->getJson($this->api("batch/{$id}/print"))->assertOk()->assertJsonPath('data.files.0.id', $a->id)->assertJsonPath('data.files.0.qr_value', 'RES-2016-31');

        $this->patchJson($this->api("batch/{$id}/print"))->assertOk();
        $this->assertSame('printed', PrintLabelBatch::find($id)->status);
        $this->getJson($this->api('batches?status=printed'))->assertJsonCount(1, 'data');
        $this->deleteJson($this->api("batch/{$id}"))->assertStatus(400);

        // A generated (not yet printed) batch can be deleted, and its file is free to label again.
        $b = $this->file('RES-2016-32');
        $id2 = $this->postJson($this->api('batch'), ['source' => 'file_indexings', 'label_format' => '30-in-1', 'orientation' => 'portrait', 'file_ids' => [$b->id]])->json('data.batch_id');
        $this->deleteJson($this->api("batch/{$id2}"))->assertOk();
        $this->getJson($this->api('files'))->assertJsonPath('data.0.file_number', 'RES-2016-32');
    }
}
