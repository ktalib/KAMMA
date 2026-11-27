<?php

namespace Tests\Feature;

use App\Support\Portal\PortalSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * Building Plan Approval on the KAMMA portal (2026-11-25, recording: the building plan portal "is coming under KAMMA,
 * not KADCA"). Copied from the KADCA portal; UI first, so saves answer "Saved (preview)" and store nothing.
 */
class PortalBpaTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private function asDemo(): static
    {
        return $this->withSession([PortalSession::KEY => [
            'username' => 'demo.applicant', 'name' => 'Demo Applicant', 'phone' => '08000000000', 'email' => null,
            'is_demo' => true, 'since' => now()->toDateTimeString(),
        ]]);
    }

    public function test_guests_are_sent_to_the_portal_sign_in(): void
    {
        foreach (['/portal/building-plans', '/portal/building-plans/apply', '/portal/building-plans/construction-progress', '/portal/building-plans/inspections'] as $url) {
            $this->get($url)->assertRedirect(route('portal.login'));
        }
    }

    public function test_every_building_plan_screen_opens_under_kamma(): void
    {
        $this->asDemo()->get('/portal/dashboard')->assertOk()->assertSee('Building Plans')->assertSee('My building plans');

        $this->asDemo()->get('/portal/building-plans')->assertOk()
            ->assertSee('DEMO-BPA/2026/00001')->assertSee('DEMO-BPA/2026/00004')->assertDontSee('KADCA')->assertDontSee('KLAES-CONTROL');
        $this->asDemo()->get('/portal/building-plans/apply')->assertOk()
            ->assertSee('Fresh Application')->assertSee('Registration')->assertDontSee('KADCA');
        $this->asDemo()->get('/portal/building-plans/applications/DEMO-BPA/2026/00002')->assertOk()->assertSee('BP-2026-DEMO2');
        $this->asDemo()->get('/portal/building-plans/applications/NOT-MINE/1')->assertNotFound();
        $this->asDemo()->get('/portal/building-plans/construction-progress')->assertOk()->assertSee('DEMO-BPA/2026/00001');
        $this->asDemo()->get('/portal/building-plans/inspections')->assertOk()->assertSee('DEMO-BPA/2026/00002');
    }

    public function test_an_application_is_checked_and_registration_pays_four_times(): void
    {
        $this->asDemo()->post('/portal/building-plans/apply', [])
            ->assertSessionHasErrors(['klaes_file_indexing_id', 'application_type', 'proposed_use', 'developer_name', 'plan_files', 'declaration']);

        $form = [
            'klaes_file_indexing_id' => $this->klaesFile(), 'application_type' => 'Fresh Application', 'proposed_use' => 'Residential',
            'developer_name' => "isma'ila habu", 'contact_address' => 'no 5 zoo road', 'developer_phone' => '08031234567',
            'submission_type' => 'New Submission', 'plan_received_as' => 'CAD drawing (DXF/DWG)',
            'plan_files' => [UploadedFile::fake()->create('ground.dxf', 10)], 'number_of_plots' => 2, 'declaration' => '1',
        ];
        $this->asDemo()->post('/portal/building-plans/apply', $form)
            ->assertRedirect(route('portal.bpa.index'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Saved (preview)') && str_contains($s, '₦30,000.00'));

        $form['application_type'] = 'Registration';
        $form['plan_files'] = [UploadedFile::fake()->create('ground.dxf', 10)];
        $this->asDemo()->post('/portal/building-plans/apply', $form)
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Registration') && str_contains($s, '₦120,000.00'));
    }

    public function test_a_real_applicants_application_is_saved_into_the_staff_list_and_shows_on_the_portal(): void
    {
        \Illuminate\Support\Facades\Storage::fake(config('kamma.bpa.uploads_disk'));
        $mine = \App\Models\Portal\PortalApplicant::create(['username' => '08031234567', 'name' => 'Aminu Bello', 'phone' => '08031234567', 'password' => 'secret123', 'verified_at' => now()]);
        $other = \App\Models\Portal\PortalApplicant::create(['username' => '08039999999', 'name' => 'Someone Else', 'phone' => '08039999999', 'password' => 'secret123', 'verified_at' => now()]);
        $as = fn ($a) => $this->withSession([PortalSession::KEY => ['id' => $a->id]]);

        $as($mine)->post('/portal/building-plans/apply', [
            'klaes_file_indexing_id' => $this->klaesFile(), 'application_type' => 'Registration', 'proposed_use' => 'Residential',
            'developer_name' => "isma'ila habu", 'contact_address' => 'no 5 zoo road', 'developer_phone' => '08031234567',
            'submission_type' => 'New Submission', 'plan_received_as' => 'CAD drawing (DXF/DWG)',
            'plan_files' => [UploadedFile::fake()->create('ground.dxf', 10)], 'number_of_plots' => 2, 'declaration' => '1',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('portal.bpa.applications.show', ['reference' => 'BPA-APP/'.now()->year.'/00001']))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'received') && str_contains($s, '₦120,000.00'));

        $app = \App\Models\Bpa\Application::query()->with(['bill', 'files'])->sole();
        $this->assertSame([$mine->id, null, 'Aminu Bello', 'Awaiting payment'], [(int) $app->portal_applicant_id, $app->received_by, $app->submitted_by_name, $app->status]);
        $this->assertSame(['Unpaid', '120000.00', 1], [$app->bill->status, (string) $app->bill->amount, $app->files->count()]);

        // On the portal: the applicant's own list and page, the bill to pay; nobody else sees it.
        $as($mine)->get('/portal/building-plans')->assertOk()->assertSee($app->application_no)->assertSee('Awaiting payment')->assertDontSee('DEMO-BPA');
        $as($mine)->get('/portal/building-plans/applications/'.$app->application_no)->assertOk()->assertSee($app->bill->bill_no)->assertSee('ground.dxf');
        $as($other)->get('/portal/building-plans/applications/'.$app->application_no)->assertNotFound();
        $as($other)->get('/portal/building-plans')->assertOk()->assertDontSee($app->application_no);

        // Staff: the Applications list marks it Portal, and "Sent from" filters portal / staff entries.
        $staff = $this->klaesUser();
        $this->actingAs($staff)->get('/development-permit/applications')->assertOk()->assertSee($app->application_no)->assertSee('Portal');
        $this->actingAs($staff)->get('/development-permit/applications?source=portal')->assertOk()->assertSee($app->application_no);
        $this->actingAs($staff)->get('/development-permit/applications?source=staff')->assertOk()->assertDontSee($app->application_no)->assertSee('No applications match');
        $this->actingAs($staff)->get('/development-permit/applications/'.$app->id)->assertOk()->assertSee('Sent by the applicant on the KLAES-METRO portal');
    }

    public function test_progress_and_inspection_requests_answer_saved_preview(): void
    {
        $this->asDemo()->post('/portal/building-plans/construction-progress', [
            'application' => 'DEMO-BPA/2026/00001', 'construction_stage' => 'Foundation', 'photos' => [UploadedFile::fake()->image('site.jpg')],
            'latitude' => 12.0022, 'longitude' => 8.5920, 'captured_at' => now()->toIso8601String(),
        ])->assertSessionHasNoErrors()->assertSessionHas('status', fn ($s) => str_contains($s, 'Saved (preview)'));

        $this->asDemo()->post('/portal/building-plans/construction-progress', [
            'application' => 'DEMO-BPA/2026/00004', 'construction_stage' => 'Foundation', 'photos' => [UploadedFile::fake()->image('site.jpg')],
            'latitude' => 12, 'longitude' => 8, 'captured_at' => now()->toIso8601String(),
        ])->assertSessionHasErrors('application');

        $day = now()->startOfDay()->addDays(2);
        while ($day->isWeekend()) {
            $day->addDay();
        }
        $this->asDemo()->post('/portal/building-plans/inspections', [
            'application' => 'DEMO-BPA/2026/00002', 'date' => $day->toDateString(), 'time' => '11:00', 'contact_phone' => '08031234567',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', fn ($s) => str_contains($s, 'Saved (preview)'));
    }
}
