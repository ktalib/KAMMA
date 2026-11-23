<?php

namespace Tests\Feature;

use App\Models\Tracking\FileTracker;
use App\Models\Tracking\Unit;
use App\Models\User;
use App\Services\Kamma\DeskFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KammaFiles;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * KAMMA File Tracking (KLAES concept): tracking IDs and QR labels, Send / Receive between units, and the physical
 * shadow file following the KAMMA desk decisions (PS → MD KAMMA → Department → …). Never mixed with KLAES land-file tracking.
 */
class TrackingTest extends TestCase
{
    use KammaFiles, KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 22));
        $this->officer = $this->klaesUser();
    }

    private function unit(string $name): Unit
    {
        return Unit::where('name', $name)->firstOrFail();
    }

    public function test_commissioning_starts_tracking_and_sends_the_file_to_the_md(): void
    {
        $f = $this->intakeFile();
        $t = FileTracker::where('file_indexing_id', $f->id)->sole();

        $this->assertMatchesRegularExpression('/^KMA-TRK-[2-9A-HJ-NP-Z]{8}$/', $t->tracking_id);
        $this->assertSame('RES-2016-452', $t->reference);
        $this->assertSame('in_transit', $t->status);
        $this->assertSame($this->unit('Registry')->id, (int) $t->current_unit_id);
        $this->assertSame($this->unit("MD KAMMA's Office")->id, (int) $t->to_unit_id);
        $this->assertSame(['logged', 'sent'], $t->movements()->orderBy('id')->pluck('action')->all());
        $this->assertSame('md', $f->stage);
    }

    public function test_the_physical_file_follows_the_desk_decisions(): void
    {
        $f = $this->intakeFile();
        $t = FileTracker::where('file_indexing_id', $f->id)->sole();

        // The MD acts before the file was received: it is received there, then sent to the Department.
        app(DeskFlow::class)->decide($f, 'recommended', null, $this->officer);
        $t->refresh();
        $this->assertSame('in_transit', $t->status);
        $this->assertSame($this->unit("MD KAMMA's Office")->id, (int) $t->current_unit_id);
        $this->assertSame($this->unit('Department')->id, (int) $t->to_unit_id);

        // Received at the Department, which returns it: it goes back to the MD.
        $this->actingAs($this->officer)->post(route('tracking.receive', $t))->assertSessionHasNoErrors();
        app(DeskFlow::class)->decide($f->fresh(), 'not_recommended', 'Survey plan missing.', $this->officer);
        $t->refresh();
        $this->assertSame($this->unit("MD KAMMA's Office")->id, (int) $t->to_unit_id);
        $this->assertSame('Not recommended by the Department; returned', $t->movements()->latest('id')->first()->purpose);
    }

    public function test_a_mail_is_logged_sent_and_received(): void
    {
        $t = app(\App\Services\Tracking\FileTracking::class)->log([
            'kind' => 'mail', 'reference' => 'KAMMA/MD/12', 'title' => 'OBJECTION TO BUILDING ON PLOT 3075', 'mail_from' => 'RESIDENTS OF KUMBOTSO',
            'unit_id' => $this->unit('Registry')->id, 'priority' => 'High', 'due_on' => '2026-11-30',
        ], $this->officer);
        $this->assertSame('KAMMA/MD/12', $t->reference);

        // Not to the unit it is already in, and not twice while in transit.
        $this->actingAs($this->officer)->post(route('tracking.send', $t), ['to_unit_id' => $this->unit('Registry')->id, 'purpose' => 'x'])
            ->assertSessionHasErrors('to_unit_id', null, 'send');
        $this->actingAs($this->officer)->post(route('tracking.send', $t), ['to_unit_id' => $this->unit("MD KAMMA's Office")->id, 'purpose' => 'For the MD'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->officer)->post(route('tracking.send', $t), ['to_unit_id' => $this->unit('Archive')->id, 'purpose' => 'x'])
            ->assertSessionHasErrors('to_unit_id', null, 'send');

        $this->actingAs($this->officer)->getJson('/file-tracking/movement/data?office='.$this->unit("MD KAMMA's Office")->id.'&kind=mails&dir=incoming&state=pending')
            ->assertOk()->assertJsonPath('rows.0.tracking_id', $t->tracking_id)->assertJsonPath('rows.0.can_receive', true)
            ->assertJsonPath('counts.pending.mails', 1);

        $this->actingAs($this->officer)->post(route('tracking.receive', $t), ['shelf_location' => 'md cabinet 2'])->assertSessionHasNoErrors();
        $t->refresh();
        $this->assertSame('in_unit', $t->status);
        $this->assertSame($this->unit("MD KAMMA's Office")->id, (int) $t->current_unit_id);
        $this->assertSame('MD CABINET 2', $t->shelf_location);
        $this->actingAs($this->officer)->post(route('tracking.receive', $t))->assertSessionHasErrors('tracker');
    }

    public function test_track_file_archive_shows_the_file_details(): void
    {
        $this->intakeFile();
        $t = FileTracker::sole();
        app(\App\Services\Tracking\FileTracking::class)->receive($t, null, $this->officer, 'RACK 3 SHELF 2');

        $this->actingAs($this->officer)->get('/file-tracking/find')->assertOk()->assertSee('Archive Search')->assertSee('File Details');
        $this->actingAs($this->officer)->getJson('/file-tracking/find/lookup?q='.$t->tracking_id)
            ->assertJson(['found' => true, 'file_number' => 'RES-2016-452', 'land_use' => 'RESIDENTIAL', 'origin' => 'Registry',
                'destination' => "MD KAMMA's Office", 'rack' => '3', 'shelf_no' => '2', 'full_rack_shelf' => 'RACK 3 SHELF 2']);
        $this->actingAs($this->officer)->getJson('/file-tracking/find/lookup?q=RES-2099-9')->assertJson(['found' => false]);
    }

    public function test_file_movement_department_scans_and_logs_manually(): void
    {
        $this->intakeFile();
        $t = FileTracker::sole();
        $md = $this->unit("MD KAMMA's Office")->id;

        $this->actingAs($this->officer)->get('/file-tracking/movement?unit='.$md)->assertOk()
            ->assertSee('File Movement (Department)')->assertSee('Pending Receipt')->assertSee('Log manually');
        $this->actingAs($this->officer)->getJson('/file-tracking/movement/scan?q='.strtolower($t->tracking_id))
            ->assertJson(['found' => true, 'file_number' => 'RES-2016-452', 'status' => 'in_transit', 'to_unit_id' => $md]);
        $this->actingAs($this->officer)->getJson('/file-tracking/movement/scan?q=RES-2016-452')->assertJson(['found' => true]);
        $this->actingAs($this->officer)->getJson('/file-tracking/movement/scan?q=NOTHING')->assertJson(['found' => false]);
        $this->actingAs($this->officer)->getJson('/file-tracking/movement/data?office='.$md)->assertJson(['counts' => ['pending' => ['files' => 1, 'total' => 1]]]);

        $this->actingAs($this->officer)->postJson('/file-tracking/movement/log', ['office' => $md, 'kind' => 'mail', 'reference' => 'kamma/md/7', 'title' => 'site visit report'])
            ->assertOk()->assertJsonStructure(['tracking_id', 'label_url']);
        $mail = FileTracker::where('kind', 'mail')->sole();
        $this->assertSame(['KAMMA/MD/7', 'SITE VISIT REPORT', 'in_unit', $md], [$mail->reference, $mail->title, $mail->status, (int) $mail->current_unit_id]);
        $this->actingAs($this->officer)->postJson('/file-tracking/movement/log', ['office' => $md, 'kind' => 'mail'])->assertStatus(422);
    }

    public function test_the_page_label_and_dashboard_show_the_tracking(): void
    {
        $f = $this->intakeFile();
        $t = FileTracker::sole();

        $this->actingAs($this->officer)->get(route('tracking.show', $t))
            ->assertOk()->assertSee('<svg', false)->assertSee("Receive in MD KAMMA's Office")
            ->assertSeeInOrder(['Logged into Registry', "Sent from Registry to MD KAMMA's Office"]);
        $this->actingAs($this->officer)->get(route('tracking.label', $t))->assertOk()->assertSee('<svg', false)->assertSee('RES-2016-452')->assertSee('KLAES-METRO');
        $this->actingAs($this->officer)->get('/file-tracking')->assertOk()->assertSee('File Tracker Dashboard')->assertSee("MD KAMMA's Office");
        $this->actingAs($this->officer)->get(route('kamma.shadow-files.show', $f))->assertOk()->assertSee($t->tracking_id);
    }

    public function test_units_can_be_added_renamed_and_retired_but_not_while_holding_files(): void
    {
        $this->actingAs($this->officer)->post('/file-tracking/units', ['name' => '  Survey   Unit '])->assertSessionHasNoErrors();
        $this->assertTrue(Unit::where('name', 'Survey Unit')->exists());
        $this->actingAs($this->officer)->post('/file-tracking/units', ['name' => 'survey unit'])->assertSessionHasErrors('name', null, 'unit');

        $this->intakeFile(); // a file now heads to the MD KAMMA's Office
        $md = $this->unit("MD KAMMA's Office");
        $this->actingAs($this->officer)->patch(route('tracking.units.update', $md), ['name' => "MD KAMMA's Office", 'is_active' => '0'])
            ->assertSessionHasErrors('name', null, 'unit');
        $this->assertTrue($md->fresh()->is_active);

        $survey = Unit::where('name', 'Survey Unit')->sole();
        $this->actingAs($this->officer)->patch(route('tracking.units.update', $survey), ['name' => 'Survey Section', 'is_active' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($survey->fresh()->is_active);
        $this->assertSame('Survey Section', $survey->fresh()->name);
    }

    public function test_the_menu_links_file_tracking(): void
    {
        $html = $this->actingAs($this->officer)->get('/file-tracking/find')->getContent();

        foreach (['tracking.dashboard', 'tracking.create', 'tracking.search', 'tracking.movement'] as $name) {
            $this->assertStringContainsString('href="'.route($name).'"', $html);
        }
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('tracking.find'), '#').'"\s+aria-current="page"#', $html);
    }
}
