<?php

namespace Tests\Feature\Bpa;

use App\Models\Bpa\Application;
use App\Models\Bpa\BpFile;
use App\Models\Bpa\BpGrouping;
use App\Models\Bpa\BpZone;
use App\Models\User;
use App\Services\Bpa\SubmitApplication;
use App\Support\Bpa\BpaStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * Building Plan Files (user briefing 2026-11-25): the grouping by year, indexing an existing BP file under its
 * number, commissioning a new one with the next serial, the zone lookup with Other, and the application that starts
 * from a BP file (Fresh Application or Registration at 4 × the fee).
 */
class BpFileTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 25));
        $this->officer = $this->klaesUser(['rank' => 'Architect I']);
        $this->artisan('kamma:bp-grouping', ['--from' => 2025, '--to' => 2026, '--per-year' => 20, '--commit' => true])->assertSuccessful();
    }

    private function zone(string $name): int
    {
        return BpZone::where('name', $name)->value('id');
    }

    private function index(array $overrides = [])
    {
        return $this->actingAs($this->officer)->post(route('bpa.bp-files.store-indexed'), array_merge([
            'klaes_file_indexing_id' => $this->klaesFile(),
            'bp_year' => 2025,
            'bp_serial' => 7,
            'bp_zone_id' => $this->zone('Zone II'),
            'bp_file_title' => "isma'ila  habu",
            'registry' => 'Registry 1',
            'batch_no' => 'B-12',
        ], $overrides));
    }

    public function test_the_grouping_is_generated_once_per_number_and_can_be_extended(): void
    {
        $this->assertSame(40, BpGrouping::count());
        $this->assertSame('BP-2025-1', BpGrouping::where('year', 2025)->orderBy('serial')->value('bp_file_no'));

        $this->artisan('kamma:bp-grouping', ['--from' => 2025, '--to' => 2026, '--per-year' => 20, '--commit' => true])->assertSuccessful();
        $this->assertSame(40, BpGrouping::count());

        $this->artisan('kamma:bp-grouping', ['--from' => 2025, '--to' => 2026, '--per-year' => 25])->assertSuccessful();
        $this->assertSame(40, BpGrouping::count(), 'a dry run writes nothing');

        $this->artisan('kamma:bp-grouping', ['--from' => 2025, '--to' => 2026, '--per-year' => 25, '--commit' => true])->assertSuccessful();
        $this->assertSame(50, BpGrouping::count());
    }

    public function test_an_existing_bp_file_is_indexed_under_its_grouping_number_with_the_land_file_from_klaes(): void
    {
        $this->index(['district' => 'TYPED DISTRICT'])->assertRedirect(route('bpa.bp-files.indexed'))->assertSessionHasNoErrors();

        $file = BpFile::sole();
        $this->assertSame('BP-2025-7', $file->bp_file_no);
        $this->assertSame(BpFile::INDEXED, $file->origin);
        $this->assertTrue($file->land_in_klaes);
        $this->assertSame('RES-2016-452', $file->land_file_number);
        $this->assertSame('WESTERN BY- PASS', $file->district, 'the land half comes from KLAES, never from the form');
        $this->assertSame('Residential', ucfirst(strtolower($file->land_use)));
        $this->assertSame('Zone II', $file->zone_name);
        $this->assertSame("ISMA'ILA HABU", $file->bp_file_title);
        $this->assertSame('Amina Bello', $file->indexed_by_name);

        $row = BpGrouping::where('bp_file_no', 'BP-2025-7')->sole();
        $this->assertSame(BpGrouping::INDEXED, $row->status);
        $this->assertSame($file->id, (int) $row->bp_file_id);
    }

    public function test_the_land_file_tracking_id_shows_on_the_form_from_the_klaes_grouping_and_is_not_stored(): void
    {
        $landId = $this->klaesFile(['tracking_id' => 'TRK-NOT-THIS-ONE']);
        \Illuminate\Support\Facades\DB::connection('klaes')->table('grouping')->insert([
            ['awaiting_fileno' => 'RES-2016-452', 'tracking_id' => 'TRK-OLD-DELETED', 'deleted_at' => '2026-01-01'],
            ['awaiting_fileno' => 'RES-2016-452', 'tracking_id' => 'TRK-K1RE0KLN-A7MUK', 'deleted_at' => null],
        ]);

        $this->actingAs($this->officer)->getJson(route('bpa.bp-files.land', $landId))->assertOk()
            ->assertJsonPath('file.tracking_id', 'TRK-K1RE0KLN-A7MUK');
        $this->actingAs($this->officer)->get(route('bpa.bp-files.create-indexed'))->assertOk()->assertSee('Tracking ID');

        $this->index(['klaes_file_indexing_id' => $landId])->assertSessionHasNoErrors();
        $file = BpFile::sole();
        $this->assertArrayNotHasKey('tracking_id', $file->getAttributes(), 'nothing is copied onto the BP file');
        $this->actingAs($this->officer)->get(route('bpa.bp-files.edit', $file))->assertOk()->assertSee('TRK-K1RE0KLN-A7MUK');
        $this->actingAs($this->officer)->get(route('bpa.bp-files.indexed'))->assertOk()->assertDontSee('TRK-K1RE0KLN-A7MUK');
    }

    public function test_every_grouping_number_has_a_bp_tracking_id_shown_on_the_forms_and_not_stored_on_the_bp_file(): void
    {
        $ids = BpGrouping::pluck('tracking_id');
        $this->assertSame(40, $ids->filter()->unique()->count(), 'the grouping command gives each number its own ID');
        $this->assertMatchesRegularExpression('/^BP-TRK-[2-9A-HJ-NP-Z]{8}$/', $ids->first());

        // An extension gives the new numbers theirs; the old ones keep theirs.
        $before = BpGrouping::where('bp_file_no', 'BP-2025-7')->value('tracking_id');
        $this->artisan('kamma:bp-grouping', ['--from' => 2025, '--to' => 2026, '--per-year' => 25, '--commit' => true])->assertSuccessful();
        $this->assertSame(0, BpGrouping::whereNull('tracking_id')->count());
        $this->assertSame($before, BpGrouping::where('bp_file_no', 'BP-2025-7')->value('tracking_id'));

        $this->actingAs($this->officer)->getJson(route('bpa.bp-files.check-number', ['year' => 2025, 'serial' => 7]))
            ->assertJsonPath('state', 'available')->assertJsonPath('tracking_id', $before);
        $this->actingAs($this->officer)->get(route('bpa.bp-files.create-indexed'))->assertOk()->assertSee('BP Tracking ID');

        $this->index()->assertSessionHasNoErrors();
        $file = BpFile::sole();
        $this->assertArrayNotHasKey('tracking_id', $file->getAttributes());
        $this->actingAs($this->officer)->get(route('bpa.bp-files.edit', $file))->assertOk()->assertSee($before);
        $this->actingAs($this->officer)->get(route('bpa.bp-files.indexed'))->assertOk()->assertDontSee($before);

        $next = BpGrouping::where('bp_file_no', 'BP-2026-1')->value('tracking_id');
        $this->actingAs($this->officer)->get(route('bpa.bp-files.create-commissioned'))->assertOk()->assertSee('BP-2026-1')->assertSee($next);
    }

    public function test_commissioning_past_the_grouping_adds_the_number_with_its_tracking_id(): void
    {
        $this->travelTo(now()->setDate(2027, 1, 3));
        $this->actingAs($this->officer)->post(route('bpa.bp-files.store-commissioned'), [
            'klaes_file_indexing_id' => $this->klaesFile(), 'bp_zone_id' => $this->zone('Zone I'),
        ])->assertSessionHasNoErrors();

        $row = BpGrouping::where('bp_file_no', 'BP-2027-1')->sole();
        $this->assertSame(BpGrouping::COMMISSIONED, $row->status);
        $this->assertMatchesRegularExpression('/^BP-TRK-/', $row->tracking_id);
    }

    public function test_a_used_number_or_one_outside_the_grouping_is_refused(): void
    {
        $this->index()->assertSessionHasNoErrors();
        $this->index()->assertSessionHasErrors('bp_serial');
        $this->index(['bp_serial' => 21])->assertSessionHasErrors('bp_serial');
        $this->index(['bp_year' => 2024])->assertSessionHasErrors('bp_serial');
        $this->index(['bp_zone_id' => ''])->assertSessionHasErrors('bp_zone_id');
        $this->assertSame(1, BpFile::count());
    }

    public function test_a_new_file_is_commissioned_above_the_highest_number_used_that_year(): void
    {
        $this->index(['bp_year' => 2026, 'bp_serial' => 5])->assertSessionHasNoErrors();

        $this->actingAs($this->officer)->get(route('bpa.bp-files.create-commissioned'))->assertOk()->assertSee('BP-2026-6');

        foreach (['BP-2026-6', 'BP-2026-7'] as $expected) {
            $this->actingAs($this->officer)->post(route('bpa.bp-files.store-commissioned'), [
                'klaes_file_indexing_id' => $this->klaesFile(), 'bp_zone_id' => $this->zone('Zone I'),
            ])->assertSessionHasNoErrors();
            $file = BpFile::latest('id')->first();
            $this->assertSame($expected, $file->bp_file_no);
            $this->assertSame(BpFile::COMMISSIONED, $file->origin);
            $this->assertSame(BpGrouping::COMMISSIONED, BpGrouping::where('bp_file_no', $expected)->value('status'));
        }

        // Commissioning takes no number from the form.
        $this->actingAs($this->officer)->post(route('bpa.bp-files.store-commissioned'), [
            'klaes_file_indexing_id' => $this->klaesFile(), 'bp_zone_id' => $this->zone('Zone I'), 'bp_serial' => 3,
        ])->assertSessionHasErrors('bp_serial');
    }

    public function test_a_land_file_missing_from_klaes_is_typed_and_flagged(): void
    {
        $this->index(['klaes_file_indexing_id' => '', 'land_not_in_klaes' => '1', 'land_file_number' => 'COM-1990-12',
            'district' => 'fagge', 'lga' => 'fagge', 'plot_no' => '44'])->assertSessionHasNoErrors();

        $file = BpFile::sole();
        $this->assertFalse($file->land_in_klaes);
        $this->assertNull($file->klaes_file_indexing_id);
        $this->assertSame('COM-1990-12', $file->land_file_number);
        $this->assertSame('FAGGE', $file->district);
        $this->assertSame('COM', $file->land_use_code);

        $this->index(['bp_serial' => 8, 'klaes_file_indexing_id' => '', 'land_not_in_klaes' => '1'])->assertSessionHasErrors('land_file_number');
    }

    public function test_other_zone_is_saved_and_set_later_without_touching_the_number_or_land_file(): void
    {
        $this->index(['bp_zone_id' => $this->zone('Other')])->assertSessionHasNoErrors();
        $file = BpFile::sole();
        $this->assertSame('Other', $file->zone_name);

        $this->actingAs($this->officer)->get(route('bpa.bp-files.index', ['zone' => $this->zone('Other')]))->assertOk()->assertSee('BP-2025-7');

        $this->actingAs($this->officer)->put(route('bpa.bp-files.update', $file), ['bp_zone_id' => $this->zone('UC'), 'bp_file_title' => 'NEW TITLE'])
            ->assertSessionHasNoErrors();
        $file->refresh();
        $this->assertSame('UC', $file->zone_name);
        $this->assertSame('BP-2025-7', $file->bp_file_no);

        $this->actingAs($this->officer)->put(route('bpa.bp-files.update', $file), ['bp_zone_id' => $this->zone('UC'), 'land_file_number' => 'X'])
            ->assertSessionHasErrors('land_file_number');
    }

    public function test_the_register_shows_both_bands_and_the_forms_open(): void
    {
        $this->index()->assertSessionHasNoErrors();

        $this->actingAs($this->officer)->get(route('bpa.bp-files.index'))->assertOk()
            ->assertSee('Land File · KLAES indexing')->assertSee('Building Plan File')->assertSee('BP-2025-7')->assertSee('RES-2016-452')
            ->assertSee('Start application');
        $this->actingAs($this->officer)->get(route('bpa.bp-files.create-indexed'))->assertOk()->assertSee('Index Existing BP File');
        $this->actingAs($this->officer)->get(route('bpa.bp-files.edit', BpFile::sole()))->assertOk()->assertSee('BP-2025-7');
        $this->actingAs($this->officer)->get(route('bpa.bp-files.grouping'))->assertOk()->assertSee('BP-2025-1')->assertSee('BP-2025-20');

        $this->actingAs($this->officer)->getJson(route('bpa.bp-files.check-number', ['year' => 2025, 'serial' => 7]))->assertJson(['state' => 'used']);
        $this->actingAs($this->officer)->getJson(route('bpa.bp-files.check-number', ['year' => 2025, 'serial' => 8]))->assertJson(['state' => 'available']);
        $this->actingAs($this->officer)->getJson(route('bpa.bp-files.check-number', ['year' => 1999, 'serial' => 8]))->assertJson(['state' => 'missing']);
    }

    public function test_each_form_has_a_list_page_with_its_dashboard_first(): void
    {
        $this->index()->assertSessionHasNoErrors();

        // DMS > Indexing: the indexed BP files + dashboard, then the form one click on.
        $this->actingAs($this->officer)->get(route('bpa.bp-files.indexed'))->assertOk()
            ->assertSee('Indexed BP Files')->assertSee('Indexed today')->assertSee('Total indexed')
            ->assertSee('BP-2025-7')->assertSee('RES-2016-452')->assertSee(route('bpa.bp-files.create-indexed'));
        $this->assertSame(url('development-permit/bp-files/index-existing'), route('bpa.bp-files.indexed'));

        // Commissioning lists only commissioned files.
        $this->actingAs($this->officer)->get(route('bpa.bp-files.commissioned'))->assertOk()
            ->assertSee('Commissioned BP Files')->assertSee('Total commissioned')->assertSee('No new BP file has been commissioned yet.')
            ->assertDontSee('BP-2025-7')->assertSee(route('bpa.bp-files.create-commissioned'));
    }

    private function applyFrom(BpFile $file, string $type): Application
    {
        return app(SubmitApplication::class)->handle([
            'bp_file_id' => $file->id,
            'application_type' => $type,
            'proposed_use' => 'Residential',
            'developer_name' => "ISMA'ILA HABU",
            'contact_address' => 'NO 5 ZOO ROAD, KANO',
            'developer_phone' => '08031234567',
            'submission_type' => 'New Submission',
            'plan_received_as' => 'Scanned at the counter',
            'number_of_plots' => 1,
        ], [UploadedFile::fake()->create('booklet.pdf', 100)], $this->officer)->load('bill');
    }

    public function test_an_application_starts_from_a_bp_file_and_registration_pays_four_times(): void
    {
        $this->index()->assertSessionHasNoErrors();
        $file = BpFile::sole();

        $this->actingAs($this->officer)->get(route('bpa.applications.create', ['bp_file' => $file->id]))->assertOk()->assertSee('BP-2025-7');

        $application = $this->applyFrom($file, 'Registration');
        $this->assertSame('BP-2025-7', $application->bp_file_no);
        $this->assertSame('Registration', $application->application_type);
        $this->assertSame('RES-2016-452', $application->file_number);
        $this->assertSame('60000.00', (string) $application->bill->amount);
        $this->assertSame($application->id, (int) $file->fresh()->bpa_application_id);

        // One application per BP file.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->applyFrom($file->fresh(), 'Fresh Application');
    }

    public function test_paying_an_application_with_a_bp_file_sends_it_to_the_director_without_commissioning(): void
    {
        $this->index()->assertSessionHasNoErrors();
        $application = $this->applyFrom(BpFile::sole(), 'Fresh Application');
        $this->assertSame('15000.00', (string) $application->bill->amount);

        $this->actingAs($this->officer)->post(route('bpa.payments.store', $application->bill), [
            'bill_id' => $application->bill->id, 'receipt_no' => 'rcp-9', 'paid_on' => '2026-11-25',
            'amount_paid' => '15,000.00', 'payment_channel' => 'Bank',
        ])->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame(BpaStage::DIRECTOR, $application->stage);
        $this->assertSame('BP-2025-7', $application->bp_file_no);
        $this->assertSame('paid', $application->movements()->value('action'));
        $this->assertSame(1, BpFile::count(), 'no second BP file');
    }

    public function test_the_older_commissioning_path_uses_the_same_numbers_and_fills_the_register(): void
    {
        $this->index(['bp_year' => 2026, 'bp_serial' => 3])->assertSessionHasNoErrors();

        $application = app(SubmitApplication::class)->handle([
            'klaes_file_indexing_id' => $this->klaesFile(), 'proposed_use' => 'Residential', 'developer_name' => 'A B',
            'contact_address' => 'KANO', 'developer_phone' => '08031234567', 'submission_type' => 'New Submission',
            'plan_received_as' => 'Scanned at the counter', 'number_of_plots' => 1,
        ], [UploadedFile::fake()->create('booklet.pdf', 100)], $this->officer)->load('bill');
        $this->assertSame('Fresh Application', $application->application_type);

        $this->actingAs($this->officer)->post(route('bpa.payments.store', $application->bill), [
            'bill_id' => $application->bill->id, 'receipt_no' => 'rcp-10', 'paid_on' => '2026-11-25',
            'amount_paid' => '15000', 'payment_channel' => 'Bank',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->officer)->post(route('bpa.commissioning.store', $application))->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame('BP-2026-4', $application->bp_file_no);
        $bp = BpFile::where('bp_file_no', 'BP-2026-4')->sole();
        $this->assertSame($application->id, (int) $bp->bpa_application_id);
        $this->assertSame($bp->id, (int) $application->bp_file_id);
        $this->assertSame(BpGrouping::COMMISSIONED, BpGrouping::where('bp_file_no', 'BP-2026-4')->value('status'));
    }
}
