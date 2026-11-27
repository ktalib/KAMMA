<?php

namespace Tests\Feature;

use App\Models\Dms\FileIndexing;
use App\Models\Dms\PageTyping;
use App\Models\Dms\Scanning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * Doc-WARE actions (copied from KLAES, user 2026-11-25): Delete File (Super Admin), Delete page, Rotate, Replace, QC and
 * Edit Type. Nothing is erased: images go to EDMS/_deleted with a manifest; the indexing record stays.
 */
class DocWareActionsTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->officer = $this->klaesUser();
    }

    /** A matched file with $n typed pages. */
    private function typedFile(int $n = 2): FileIndexing
    {
        $id = $this->klaesFile(['registry' => 'Lands Registry']);
        $this->actingAs($this->officer)->postJson(route('dms.indexing.store'), ['klaes_file_indexing_id' => $id])->assertOk();
        $f = FileIndexing::query()->where('klaes_file_indexing_id', $id)->firstOrFail();
        $files = [];
        for ($i = 1; $i <= $n; $i++) {
            $files[] = UploadedFile::fake()->image("p{$i}.jpg", 40, 60);
        }
        $this->actingAs($this->officer)->post(route('dms.scanning.upload'), ['file_indexing_id' => $f->id, 'paper_size' => 'A4', 'documents' => $files], ['Accept' => 'application/json'])->assertOk();
        foreach (Scanning::query()->where('file_indexing_id', $f->id)->get() as $s) {
            $this->actingAs($this->officer)->postJson(route('dms.pagetyping.save', $s), ['page_type' => 'Town Planning', 'page_subtype' => 'Location Plan'])->assertOk();
        }

        return $f;
    }

    public function test_delete_file_is_for_super_admins_and_keeps_the_indexing_record(): void
    {
        $f = $this->typedFile();
        $paths = Scanning::query()->pluck('document_path')->all();

        $this->actingAs($this->officer)->get(route('archive.index'))->assertOk()->assertDontSee(route('archive.delete-file', $f), false);
        $this->actingAs($this->officer)->post(route('archive.delete-file', $f), ['reason' => 'Scanned on the wrong file'])->assertForbidden();

        $admin = $this->klaesUser(['username' => 'sa', 'email' => 'sa@example.test', 'type' => 'Supper Admin']);
        $this->actingAs($admin)->get(route('archive.index'))->assertOk()->assertSee(route('archive.delete-file', $f), false);
        $this->actingAs($admin)->post(route('archive.delete-file', $f), [])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('archive.delete-file', $f), ['reason' => 'Scanned on the wrong file'])
            ->assertRedirect(route('archive.index'))->assertSessionHas('status', fn ($s) => str_contains($s, '2 page(s) moved to the EDMS trash'));

        $this->assertSame(0, PageTyping::query()->count());
        $this->assertSame(2, Scanning::query()->where('is_deleted', true)->count());
        $this->assertFalse($f->fresh()->is_deleted);
        $edms = Storage::disk('edms');
        $trash = collect($edms->allFiles('_deleted'));
        $this->assertCount(1, $trash->filter(fn ($p) => str_ends_with($p, 'manifest.json')));
        foreach ($paths as $p) {
            $edms->assertMissing($p);
            $this->assertTrue($trash->contains(fn ($t) => str_ends_with($t, 'files/'.$p)));
        }
        $manifest = json_decode($edms->get($trash->first(fn ($p) => str_ends_with($p, 'manifest.json'))), true);
        $this->assertSame(['Delete File', 'Scanned on the wrong file', 2, 2], [$manifest['action'], $manifest['reason'], count($manifest['scannings']), count($manifest['page_typings'])]);
        $this->actingAs($admin)->get(route('archive.index'))->assertOk()->assertSee('No archived file yet'); // the flash names the file, the grid is empty
    }

    public function test_viewer_edit_mode_delete_rotate_replace_qc_and_edit_type(): void
    {
        $f = $this->typedFile(3);
        [$a, $b, $c] = Scanning::query()->orderBy('id')->get()->all();
        $edms = Storage::disk('edms');
        $this->actingAs($this->officer)->get(route('archive.show', [$f, 'page' => $b->id]))->assertOk()
            ->assertSee('Save rotation')->assertSee('Save Classification')->assertSee('Opens when KAMMA has its own registries');

        // Rotate: the page is turned in place (40x60 becomes 60x40); the original is kept in the trash.
        $this->actingAs($this->officer)->post(route('archive.pages.rotate', $a), ['degrees' => 90])->assertRedirect(route('archive.show', [$f, 'page' => $a->id]));
        [$w, $h] = getimagesizefromstring($edms->get($a->document_path));
        $this->assertSame([60, 40], [$w, $h]);
        $this->actingAs($this->officer)->post(route('archive.pages.rotate', $a), ['degrees' => 45])->assertSessionHasErrors('degrees');

        // Replace: new image on the page, the old one in the trash, the page type stays.
        $old = $b->document_path;
        $this->actingAs($this->officer)->post(route('archive.pages.replace', $b), ['image' => UploadedFile::fake()->image('better.jpg', 50, 70)])->assertSessionHasNoErrors();
        $b->refresh();
        $this->assertNotSame($old, $b->document_path);
        $edms->assertMissing($old);
        $edms->assertExists($b->document_path);
        $this->assertSame('better.jpg', $b->original_filename);
        $this->assertNotNull($b->pageTyping);

        // QC and Edit Type.
        $this->actingAs($this->officer)->post(route('archive.pages.qc', $b), ['qc' => 'fail'])->assertSessionHasNoErrors();
        $this->assertSame('failed', $b->pageTyping->fresh()->qc_status);
        $this->actingAs($this->officer)->postJson(route('dms.pagetyping.save', $b), ['page_type' => 'Other', 'page_type_others' => 'Site Photo'])->assertOk();
        $this->assertSame('Site Photo', $b->pageTyping->fresh()->typeLabel());

        // Delete page: off the file, into the trash with its typing; the viewer reopens on the file.
        $this->actingAs($this->officer)->post(route('archive.pages.delete', $c))->assertRedirect(route('archive.show', $f));
        $this->assertTrue($c->fresh()->is_deleted);
        $this->assertNull(PageTyping::query()->where('scanning_id', $c->id)->first());
        $edms->assertMissing($c->document_path);
        $this->assertSame(2, Scanning::query()->where('file_indexing_id', $f->id)->where('is_deleted', false)->count());
    }
}
