<?php

namespace Tests\Feature\Bpa;

use App\Models\Bpa\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * Assessment Stage: a Building Plan Approval application is made on an existing KLAES land file,
 * checked against its land use, and raises the application bill (₦15,000 per plot).
 */
class ApplicationTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 22));
    }

    private function form(int $fileId, array $overrides = []): array
    {
        return array_merge([
            'klaes_file_indexing_id' => $fileId,
            'proposed_use' => 'Residential',
            'developer_name' => "  isma'ila   habu ",
            'contact_address' => 'no 5 zoo road, kano',
            'developer_phone' => '08031234567',
            'developer_email' => 'Habu@Example.COM',
            'submission_type' => 'New Submission',
            'documents' => ['Letter of Allocation', 'Certificate of Occupancy'],
            'drawings' => ['Site Plan', 'Architectural'],
            'plan_received_as' => 'Scanned booklet from the applicant',
            'plan_files' => [UploadedFile::fake()->create('booklet.pdf', 300, 'application/pdf')],
            'number_of_plots' => 2,
            'submitted_by_name' => 'musa ali',
            'submitted_on' => '2026-11-22',
        ], $overrides);
    }

    public function test_the_file_search_reads_live_klaes_files_only(): void
    {
        $this->klaesFile();
        $this->klaesFile(['file_number' => 'RES-2016-453', 'is_deleted' => 1]);
        $this->klaesFile(['file_number' => 'RES-2016-454', 'is_decommissioned' => 1]);

        $response = $this->actingAs($this->klaesUser())->getJson('/klaes/files/search?q=RES-2016-45');

        $response->assertOk()->assertJsonCount(1, 'files');
        $response->assertJsonPath('files.0.file_number', 'RES-2016-452');
        $response->assertJsonPath('files.0.land_use_code', 'RES');
        $response->assertJsonPath('files.0.plot_no', '3075');
    }

    public function test_the_form_offers_the_paper_form_fields_and_the_fee(): void
    {
        $response = $this->actingAs($this->klaesUser(['rank' => 'Town Planner I']))->get('/development-permit/applications/create');

        $response->assertOk();
        $response->assertSee('name="klaes_file_indexing_id"', false);
        $response->assertSee('Cadastral zone / Plot No.');
        $response->assertSee('Title Regularization/Acknowledgement');
        $response->assertSee('Police Report');
        $response->assertSee('₦15,000.00 per plot');
        $response->assertSee('.dxf', false);
        $response->assertSee('Town Planner I');
        $response->assertSeeInOrder(['Proposed building', 'Land file', 'Developer', 'Submission &amp; documents', 'Building plan', 'Fee &amp; review'], false);
        $response->assertSee('formWizard(', false);
        $response->assertSee('initial: 1 }', false);
    }

    public function test_a_returned_form_opens_at_the_first_step_with_an_error(): void
    {
        $fileId = $this->klaesFile();
        $user = $this->klaesUser();

        $this->actingAs($user)->from('/development-permit/applications/create')
            ->post('/development-permit/applications', $this->form($fileId, ['submission_type' => 'Other']))
            ->assertRedirect('/development-permit/applications/create');

        $this->actingAs($user)->get('/development-permit/applications/create')->assertSee('initial: 4 }', false);
    }

    public function test_an_application_saves_the_klaes_file_details_and_raises_the_bill(): void
    {
        $fileId = $this->klaesFile();
        $officer = $this->klaesUser(['rank' => 'Town Planner I']);

        // A posted plot number is ignored: property details always come from KLAES.
        $response = $this->actingAs($officer)->post('/development-permit/applications', $this->form($fileId, ['plot_no' => '9999']));

        $application = Application::with('bill', 'files')->sole();
        $response->assertRedirect(route('bpa.applications.show', $application));

        $this->assertSame('BPA-APP/2026/00001', $application->application_no);
        $this->assertSame('RES-2016-452', $application->file_number);
        $this->assertSame('3075', $application->plot_no);
        $this->assertSame('Kumbotso', $application->lga);
        $this->assertSame('Match', $application->land_use_check);
        $this->assertSame("ISMA'ILA HABU", $application->developer_name);
        $this->assertSame('NO 5 ZOO ROAD, KANO', $application->contact_address);
        $this->assertSame('habu@example.com', $application->developer_email);
        $this->assertSame(['Letter of Allocation', 'Certificate of Occupancy'], $application->documents_submitted);
        $this->assertSame('Amina Bello', $application->received_by_name);
        $this->assertSame('Town Planner I', $application->received_by_rank);

        $this->assertSame('BPA-BILL/2026/00001', $application->bill->bill_no);
        $this->assertSame('30000.00', $application->bill->amount);
        $this->assertSame(2, (int) $application->bill->quantity);
        $this->assertSame('Unpaid', $application->bill->status);

        $this->assertCount(1, $application->files);
        $this->assertSame('plan', $application->files[0]->kind);
        Storage::disk('local')->assertExists($application->files[0]->stored_path);
    }

    public function test_a_commercial_plan_on_a_residential_file_is_flagged_but_saved(): void
    {
        $fileId = $this->klaesFile();

        $this->actingAs($this->klaesUser())->post('/development-permit/applications', $this->form($fileId, ['proposed_use' => 'Commercial']));

        $application = Application::sole();
        $this->assertSame('Mismatch', $application->land_use_check);
        $this->assertSame('Commercial building proposed on a Residential land file.', $application->land_use_note);
    }

    public function test_a_cad_drawing_is_accepted_and_other_files_are_not(): void
    {
        $fileId = $this->klaesFile();
        $user = $this->klaesUser();

        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId, [
            'plan_received_as' => 'CAD drawing (DXF/DWG)',
            'plan_files' => [UploadedFile::fake()->create('plan.dxf', 500), UploadedFile::fake()->create('plan.dwg', 500)],
        ]))->assertSessionHasNoErrors();
        $this->assertSame(['cad', 'cad'], Application::sole()->files->pluck('kind')->all());

        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId, [
            'plan_files' => [UploadedFile::fake()->create('virus.exe', 10)],
        ]))->assertSessionHasErrors('plan_files.0');
        $this->assertSame(1, Application::count());
    }

    public function test_the_form_needs_a_land_file_a_plan_and_the_other_boxes_when_other_is_chosen(): void
    {
        $fileId = $this->klaesFile();

        $this->actingAs($this->klaesUser())->post('/development-permit/applications', $this->form($fileId, [
            'klaes_file_indexing_id' => null,
            'plan_files' => [],
            'proposed_use' => 'Other',
            'submission_type' => 'Other',
            'documents' => ['Other'],
        ]))->assertSessionHasErrors([
            'klaes_file_indexing_id', 'plan_files', 'proposed_use_other', 'submission_type_other', 'documents_other',
        ]);

        $this->assertSame(0, Application::count());
    }

    public function test_a_deleted_klaes_file_cannot_be_used(): void
    {
        $fileId = $this->klaesFile(['is_deleted' => 1]);

        $this->actingAs($this->klaesUser())->post('/development-permit/applications', $this->form($fileId))
            ->assertSessionHasErrors('klaes_file_indexing_id');

        $this->assertSame(0, Application::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_numbers_restart_on_the_first_of_january(): void
    {
        $fileId = $this->klaesFile();
        $user = $this->klaesUser();

        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId));
        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId));
        $this->travelTo(now()->setDate(2027, 1, 1));
        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId));

        $this->assertSame(
            ['BPA-APP/2026/00001', 'BPA-APP/2026/00002', 'BPA-APP/2027/00001'],
            Application::orderBy('id')->pluck('application_no')->all()
        );
    }

    public function test_the_lists_number_rows_and_filter(): void
    {
        $fileId = $this->klaesFile();
        $user = $this->klaesUser();
        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId));
        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId, ['proposed_use' => 'Industrial']));

        $this->actingAs($user)->get('/development-permit/applications')
            ->assertOk()
            ->assertSeeInOrder(['S/N', 'Plot No.', 'District', 'LGA', 'Location'])
            ->assertSeeInOrder(['BPA-APP/2026/00002', 'BPA-APP/2026/00001'])
            ->assertSee('aria-label="Actions"', false);

        $this->actingAs($user)->get('/development-permit/applications?land_use_check=Mismatch')
            ->assertSee('BPA-APP/2026/00002')
            ->assertDontSee('BPA-APP/2026/00001');

        $this->actingAs($user)->get('/development-permit/bills?search=bpa-bill/2026/00001')
            ->assertOk()
            ->assertSee('BPA-BILL/2026/00001')
            ->assertDontSee('BPA-BILL/2026/00002');
    }

    public function test_the_application_page_shows_the_bill_and_serves_its_own_files_only(): void
    {
        $fileId = $this->klaesFile();
        $user = $this->klaesUser();
        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId));
        $this->actingAs($user)->post('/development-permit/applications', $this->form($fileId));
        [$first, $second] = Application::with('files')->orderBy('id')->get()->all();

        $this->actingAs($user)->get(route('bpa.applications.show', $first))
            ->assertOk()
            ->assertSee('30,000.00')
            ->assertSee('booklet.pdf');

        $this->actingAs($user)->get(route('bpa.applications.files.download', [$first, $first->files[0]]))->assertOk();
        $this->actingAs($user)->get(route('bpa.applications.files.download', [$first, $second->files[0]]))->assertNotFound();
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get('/development-permit/applications')->assertRedirect('/login');
        $this->get('/klaes/files/search?q=RES')->assertRedirect('/login');
    }
}
