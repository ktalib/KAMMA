<?php

namespace Tests\Feature\Bpa;

use App\Models\Bpa\Application;
use App\Models\Bpa\SiteInspection;
use App\Models\User;
use App\Services\Bpa\CommissionBpFile;
use App\Services\Bpa\MoveFile;
use App\Services\Bpa\RecordPayment;
use App\Services\Bpa\SubmitApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * After the Deputy Director (Arch.): the Head of Zonal Office assigns an officer, the officer files the Site Inspection
 * Report with geocoded photos, the Head approves / returns / refers it, and Final Conveyance makes the file BPA approved.
 */
class ZonalOfficeTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $head;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 23)->setTime(10, 0));
        $this->head = $this->klaesUser(['username' => 'zhead', 'first_name' => 'Zainab', 'last_name' => 'Head', 'rank' => 'Head of Zonal Office']);
        $this->officer = $this->klaesUser(['username' => 'zoff', 'first_name' => 'Musa', 'last_name' => 'Officer', 'rank' => 'Town Planning Officer']);
    }

    private function atZonalOffice(): Application
    {
        $application = app(SubmitApplication::class)->handle([
            'klaes_file_indexing_id' => $this->klaesFile(),
            'proposed_use' => 'Residential',
            'developer_name' => "ISMA'ILA HABU",
            'contact_address' => 'NO 5 ZOO ROAD, KANO',
            'developer_phone' => '08031234567',
            'submission_type' => 'New Submission',
            'plan_received_as' => 'Scanned at the counter',
            'number_of_plots' => 1,
        ], [UploadedFile::fake()->create('booklet.pdf', 100)], $this->head);
        app(RecordPayment::class)->handle($application->bill, [
            'receipt_no' => 'R-'.$application->id, 'paid_on' => '2026-11-23', 'amount_paid' => 15000, 'payment_channel' => 'Bank',
        ], $this->head);
        $application = app(CommissionBpFile::class)->handle($application, $this->head);
        app(MoveFile::class)->decide($application, 'director', 'recommended', null, $this->head);

        return app(MoveFile::class)->decide($application->fresh(), 'deputy-director', 'recommended', null, $this->head);
    }

    private function assign(Application $a, array $over = [])
    {
        return $this->actingAs($this->head)->post(route('bpa.zonal.assign', $a), array_merge([
            'officer_id' => $this->officer->id,
            'scope' => ['Initial Site Visit', 'Inspection / Site Assessment'],
            'due_date' => '2026-11-30',
            'instructions' => 'Inspect against the approved drawings.',
        ], $over));
    }

    private function report(Application $a, array $over = [])
    {
        return $this->actingAs($this->officer)->post(route('bpa.inspection.store'), array_merge([
            'bpa_application_id' => $a->id,
            'visit_date' => '2026-11-23', 'visit_time' => '09:30', 'person_met' => 'Site engineer',
            'plot_no' => '12', 'district' => 'Nassarawa', 'land_use' => 'Residential',
            'nature_of_plot' => 'Well drained', 'slope' => 'Below 5% (relatively flat)', 'vacancy' => 'Vacant',
            'level_of_service' => 'Fully serviced',
            'front_setback' => '6.5', 'floors' => 2,
            'recommendation' => SiteInspection::RECOMMEND_APPROVAL,
            'photos' => [UploadedFile::fake()->image('site1.jpg'), UploadedFile::fake()->image('site2.jpg')],
            'photo_lat' => ['12.0021', ''], 'photo_lng' => ['8.5920', ''],
            'photo_taken_at' => ['2026-11-23T09:35:00Z', '2026-11-23T09:36:00Z'],
        ], $over));
    }

    private function review(Application $a, string $decision, ?string $remarks = null)
    {
        return $this->actingAs($this->head)->post(route('bpa.zonal.review', $a), array_filter(['decision' => $decision, 'remarks' => $remarks]));
    }

    public function test_the_full_flow_from_the_zonal_office_to_bpa_approved(): void
    {
        $a = $this->atZonalOffice();
        $this->assertSame('zonal_office', $a->stage);
        $this->actingAs($this->head)->get(route('bpa.zonal-approval'))->assertOk()->assertSee($a->bp_file_no)->assertSee('Assign officer');

        // 1. Assign.
        $this->assign($a)->assertRedirect(route('bpa.zonal-approval', ['tab' => 'assigned']))->assertSessionHasNoErrors();
        $a->refresh();
        $assignment = $a->assignments()->first();
        $this->assertSame('inspection', $a->stage);
        $this->assertSame('BPA-ZI/2026/00001', $assignment->instruction_no);
        $this->assertSame('Musa Officer', $assignment->officer_name);
        $this->actingAs($this->head)->get(route('bpa.zonal-approval', ['tab' => 'assigned']))->assertSee('BPA-ZI/2026/00001');
        $this->actingAs($this->head)->get(route('bpa.zonal.instruction', $assignment))->assertOk()->assertSee('Instruction to Inspect')->assertSee('30 November 2026');

        // 2. The officer files the report with geocoded photos.
        $this->actingAs($this->officer)->get(route('bpa.inspection', ['file' => $a->id]))->assertOk()->assertSee($a->bp_file_no);
        $res = $this->report($a)->assertSessionHasNoErrors();
        $inspection = SiteInspection::firstOrFail();
        $res->assertRedirect(route('bpa.inspection.show', $inspection));
        $this->assertSame('zonal_review', $a->fresh()->stage);
        $this->assertCount(2, $inspection->photos);
        $this->assertEqualsWithDelta(12.0021, $inspection->photos[0]->latitude, 0.00001);
        $this->assertNull($inspection->photos[1]->latitude);
        Storage::disk('local')->assertExists($inspection->photos[0]->path);
        $this->assertSame('reported', $assignment->fresh()->status);
        $this->actingAs($this->head)->get(route('bpa.inspection.show', $inspection))->assertOk()->assertSee('Awaiting the Head of Zonal Office')->assertSee('12.002100');
        $this->actingAs($this->head)->get(route('bpa.inspection.photo', $inspection->photos[0]))->assertOk();
        $this->actingAs($this->head)->get(route('bpa.field-data'))->assertOk()->assertSee($a->bp_file_no);

        // 3. The Head approves.
        $this->review($a, 'approve')->assertSessionHasNoErrors();
        $this->assertSame('conveyance', $a->fresh()->stage);
        $this->assertSame('approved', $inspection->fresh()->review_decision);
        $this->assertSame('closed', $assignment->fresh()->status);

        // 4. Final Conveyance.
        $this->actingAs($this->head)->get(route('bpa.conveyance'))->assertOk()->assertSee($a->bp_file_no);
        $res = $this->actingAs($this->head)->post(route('bpa.conveyance.store'), [
            'bpa_application_id' => $a->id, 'applicant' => "ISMA'ILA HABU",
            'date_of_approval' => '2026-11-23', 'commencement_date' => '2026-12-01', 'expiry_date' => '2028-12-01',
            'conditions' => [\App\Http\Controllers\Bpa\ZonalController::CONDITIONS[0]],
            'declarant_name' => "ISMA'ILA HABU", 'authorized_officer' => 'Zainab Head',
        ])->assertSessionHasNoErrors();
        $a->refresh();
        $this->assertSame('approved', $a->stage);
        $this->assertSame('BPA approved', $a->status);
        $this->assertNotNull($a->bpa_approved_at);
        $this->assertSame('DP/2026/00001', $a->conveyance->permit_no);
        $res->assertRedirect(route('bpa.conveyance.show', $a->conveyance));
        $this->actingAs($this->head)->get(route('bpa.conveyance.show', $a->conveyance))->assertOk()->assertSee('DEVELOPMENT PERMIT')->assertSee('DP/2026/00001')->assertDontSee('Gombe');

        $this->assertSame(
            ['commissioned', 'recommended', 'recommended', 'assigned', 'report_filed', 'report_approved', 'conveyed'],
            $a->movements()->orderBy('id')->pluck('action')->all()
        );
        $this->actingAs($this->head)->get(route('bpa.dashboard'))->assertOk();
        $this->actingAs($this->head)->get(route('bpa.applications.show', $a))->assertOk();
    }

    public function test_assigning_needs_an_active_officer_and_happens_once(): void
    {
        $a = $this->atZonalOffice();

        $this->assign($a, ['officer_id' => ''])->assertSessionHasErrors('officer_id', null, 'assign');
        $inactive = $this->klaesUser(['username' => 'gone', 'is_active' => 0]);
        $this->assign($a, ['officer_id' => $inactive->id])->assertSessionHasErrors('officer_id', null, 'assign');
        $this->assign($a, ['scope' => []])->assertSessionHasErrors('scope', null, 'assign');
        $this->assertSame('zonal_office', $a->fresh()->stage);

        $this->assign($a)->assertSessionHasNoErrors();
        $this->assign($a)->assertSessionHasErrors();
        $this->assertSame(1, $a->assignments()->count());
    }

    public function test_the_report_needs_photos_and_the_conditional_answers(): void
    {
        $a = $this->atZonalOffice();
        $this->assign($a);

        $this->report($a, ['photos' => []])->assertSessionHasErrors('photos');
        $this->report($a, ['vacancy' => 'Not vacant'])->assertSessionHasErrors(['existing_development', 'approved_earlier']);
        $this->report($a, ['nature_of_plot' => 'Other'])->assertSessionHasErrors('nature_of_plot_other');
        $this->report($a, ['recommendation' => SiteInspection::NOT_RECOMMENDED])->assertSessionHasErrors('remarks');
        $this->report($a, ['photos' => [UploadedFile::fake()->create('plan.pdf', 10)]])->assertSessionHasErrors('photos.0');

        $this->assertSame(0, SiteInspection::count());
        $this->assertSame('inspection', $a->fresh()->stage);
        $this->assertSame([], Storage::disk('local')->allFiles('kamma/bpa-site-inspections'));
    }

    public function test_a_report_cannot_be_filed_on_a_file_that_was_not_assigned(): void
    {
        $a = $this->atZonalOffice();

        $this->report($a)->assertSessionHasErrors('application');
        $this->assertSame(0, SiteInspection::count());
        $this->assertSame([], Storage::disk('local')->allFiles('kamma/bpa-site-inspections'));
    }

    public function test_the_head_returns_the_report_and_the_officer_files_again(): void
    {
        $a = $this->atZonalOffice();
        $this->assign($a);
        $this->report($a);

        $this->review($a, 'return')->assertSessionHasErrors('remarks', null, 'review');
        $this->review($a, 'return', 'Measure the setbacks.')->assertSessionHasNoErrors();
        $this->assertSame('inspection', $a->fresh()->stage);
        $this->assertSame('active', $a->assignments()->first()->status);
        $this->actingAs($this->head)->get(route('bpa.zonal-approval', ['tab' => 'assigned']))->assertSee('Report returned');

        $this->report($a, ['front_setback' => '7'])->assertSessionHasNoErrors();
        $this->assertSame('zonal_review', $a->fresh()->stage);
        $this->assertSame(2, $a->inspections()->count());
        $this->review($a, 'approve')->assertSessionHasNoErrors();
        $this->assertSame('conveyance', $a->fresh()->stage);
    }

    public function test_a_report_that_does_not_recommend_approval_cannot_be_approved_but_can_be_referred_back(): void
    {
        $a = $this->atZonalOffice();
        $this->assign($a);
        $this->report($a, ['recommendation' => SiteInspection::CONTRAVENTION, 'remarks' => 'Two extra floors built.']);

        $this->review($a, 'approve')->assertSessionHasErrors('decision', null, 'review');
        $this->assertSame('zonal_review', $a->fresh()->stage);

        $this->review($a, 'refer', 'Contravention: extra floors.')->assertSessionHasNoErrors();
        $a->refresh();
        $this->assertSame('dd_arch', $a->stage);
        $this->assertSame('referred', $a->inspections()->first()->review_decision);
        $this->actingAs($this->head)->get('/development-permit/approvals/deputy-director')->assertSee($a->bp_file_no);
    }

    public function test_conveyance_only_for_files_at_that_stage_and_dates_in_order(): void
    {
        $a = $this->atZonalOffice();
        $post = fn (array $over = []) => $this->actingAs($this->head)->post(route('bpa.conveyance.store'), array_merge([
            'bpa_application_id' => $a->id, 'applicant' => 'X', 'date_of_approval' => '2026-11-23',
            'commencement_date' => '2026-12-01', 'expiry_date' => '2028-12-01',
            'conditions' => [\App\Http\Controllers\Bpa\ZonalController::CONDITIONS[0]], 'declarant_name' => 'X', 'authorized_officer' => 'Y',
        ], $over));

        $post()->assertSessionHasErrors('application');
        $post(['expiry_date' => '2026-11-30'])->assertSessionHasErrors('expiry_date');
        $post(['conditions' => ['Made up']])->assertSessionHasErrors('conditions.0');
        $this->assertSame(0, \App\Models\Bpa\Conveyance::count());
    }

    public function test_memos_are_saved_numbered_and_printable(): void
    {
        $a = $this->atZonalOffice();

        $this->actingAs($this->head)->get(route('bpa.memo'))->assertOk();
        $this->actingAs($this->head)->post(route('bpa.memo.store'), ['to' => 'MD'])->assertSessionHasErrors(['bpa_application_id', 'from', 'subject', 'body', 'memo_date']);

        $this->actingAs($this->head)->post(route('bpa.memo.store'), [
            'bpa_application_id' => $a->id, 'to' => 'The Managing Director', 'through' => 'Director, Architectural Department',
            'from' => 'Zainab Head', 'memo_date' => '2026-11-23', 'subject' => 'Building Plan Approval: '.$a->bp_file_no,
            'body' => 'The plans were reviewed.', 'recommendation' => 'Approve.',
        ])->assertSessionHasNoErrors();

        $memo = \App\Models\Bpa\Memo::firstOrFail();
        $this->assertSame('KMA-MEMO/2026/00001', $memo->memo_no);
        $this->actingAs($this->head)->get(route('bpa.memo.show', $memo))->assertOk()->assertSee('KMA-MEMO/2026/00001')->assertSee('The plans were reviewed.');
        $this->actingAs($this->head)->get(route('bpa.memo'))->assertSee('KMA-MEMO/2026/00001');
    }
}
