<?php

namespace Tests\Feature;

use App\Models\Kamma\Bill;
use App\Models\Kamma\KsiReport;
use App\Models\Kamma\Memo;
use App\Models\Kamma\TitleStatusUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KammaFiles;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * The KAMMA screens that answered "Saved (preview)" until 2026-11-25: MD KAMMA approvals (Planning Recommendation,
 * Master KSI, Memo), the KAMMA Memo, Payments (record payment, report, CSV, e-Receipt, bill print) and Title Status Update.
 */
class KammaApprovalsPaymentsTest extends TestCase
{
    use KammaFiles, KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 0));
        $this->officer = $this->klaesUser();
    }

    private function report(string $kind = 'conversion', array $attrs = []): KsiReport
    {
        $file = $this->intakeFile([], $kind === 'master' ? 'conversion' : $kind);

        return KsiReport::create($attrs + [
            'kind' => $kind, 'file_indexing_id' => $file->id, 'file_number' => $file->file_number, 'applicant_name' => "ISMA'ILA HABU",
            'plot_number' => '3075', 'district' => 'WESTERN BY- PASS', 'lga' => 'Kumbotso', 'location' => 'WESTERN BY- PASS, KUMBOTSO, KANO STATE',
            'inspection_date' => '2026-11-20', 'existing_site_area' => 450, 'recommended_site_area' => 420, 'building_exists' => true,
            'bpa_approved' => false, 'compliance_status' => 'Obtainable', 'status' => 'submitted',
        ]);
    }

    public function test_the_md_approves_or_declines_a_planning_recommendation_once(): void
    {
        $r = $this->report();
        $this->actingAs($this->officer);

        $this->get(route('kamma.approvals.pr', 'conversion'))->assertOk()->assertSee('RES-2016-452');

        $this->post(route('kamma.approvals.decide', $r->id), ['decision' => 'declined'])->assertSessionHasErrors('remarks');
        $this->post(route('kamma.approvals.decide', $r->id), ['decision' => 'approved', 'remarks' => 'Conforms'])->assertSessionHasNoErrors();

        $r->refresh();
        $this->assertSame('approved', $r->md_decision);
        $this->assertSame('approved', $r->status);
        $this->assertSame($this->officer->name, $r->md_decided_by_name);
        $this->assertNotNull($r->md_decided_at);

        $this->post(route('kamma.approvals.decide', $r->id), ['decision' => 'declined', 'remarks' => 'Changed my mind'])->assertSessionHasErrors('decision');
        $this->assertSame('approved', $r->refresh()->md_decision);

        $this->get(route('kamma.approvals.pr', ['conversion', 'tab' => 'approved']))->assertOk()->assertSee('RES-2016-452');
        $this->get(route('kamma.approvals.pr', ['conversion', 'tab' => 'awaiting']))->assertOk()->assertDontSee('RES-2016-452');
    }

    public function test_master_ksi_approval_lists_only_master_reports(): void
    {
        $r = $this->report('master', ['post_cofo_type' => 'Sub-Division']);
        $this->actingAs($this->officer)->get(route('kamma.approvals.master-ksi'))->assertOk()->assertSee('Sub-Division');
        $this->get(route('kamma.approvals.pr', 'conversion'))->assertOk()->assertDontSee('Sub-Division');

        $this->post(route('kamma.approvals.decide', $r->id), ['decision' => 'declined', 'remarks' => 'Plot too small'])->assertSessionHasNoErrors();
        $this->assertSame('declined', $r->refresh()->status);
    }

    public function test_the_memo_follows_an_approved_recommendation_and_goes_to_the_md(): void
    {
        $r = $this->report();
        $this->actingAs($this->officer);
        $memo = ['ksi_report_id' => $r->id, 'planner_recommendation' => 'Recommended for conversion.', 'planner_recommendation_date' => '2026-11-25'];

        // Not before the MD approves the Planning Recommendation.
        $this->post(route('kamma.memos.store', 'conversion'), $memo)->assertSessionHasErrors('ksi_report_id');

        $this->post(route('kamma.approvals.decide', $r->id), ['decision' => 'approved']);
        $this->get(route('kamma.memos.index', 'conversion'))->assertOk()->assertSee('RES-2016-452');

        $this->post(route('kamma.memos.store', 'conversion'), $memo)->assertSessionHasNoErrors();
        $m = Memo::query()->sole();
        $this->assertSame('KMA-PM/2026/00001', $m->memo_no);
        $this->assertSame('submitted', $m->status);
        $this->assertSame('Plot 3075, WESTERN BY- PASS, Kumbotso, WESTERN BY- PASS, KUMBOTSO, KANO STATE', $m->property_location);

        // One memo per report; the awaiting tab empties.
        $this->post(route('kamma.memos.store', 'conversion'), $memo)->assertSessionHasErrors('ksi_report_id');
        $this->get(route('kamma.memos.index', 'conversion'))->assertOk()->assertSee('No approved Planning Recommendation is waiting for a memo');
        $this->get(route('kamma.memos.show', ['conversion', $m->id]))->assertOk()->assertSee('KMA-PM/2026/00001')->assertSee('Awaiting the MD KAMMA');

        // The MD's memo decision.
        $this->get(route('kamma.approvals.memo', 'conversion'))->assertOk()->assertSee('KMA-PM/2026/00001');
        $this->post(route('kamma.approvals.decide-memo', $m->id), ['decision' => 'declined'])->assertSessionHasErrors('md_recommendation');
        $this->post(route('kamma.approvals.decide-memo', $m->id), ['decision' => 'approved', 'md_recommendation' => 'Approved.'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $m->refresh()->status);
        $this->assertSame('2026-11-25', $m->md_recommendation_date->toDateString());
        $this->post(route('kamma.approvals.decide-memo', $m->id), ['decision' => 'declined', 'md_recommendation' => 'No'])->assertSessionHasErrors('decision');

        // Master KSI has no memo screen.
        $this->get('/kamma/master/memos')->assertNotFound();
    }

    public function test_a_payment_is_recorded_once_with_a_unique_receipt(): void
    {
        config(['kamma.bills.processing_fee' => 5000]);
        $r = $this->report();
        $this->actingAs($this->officer)->post(route('kamma.bills.store'), ['bill_type' => 'processing', 'file_indexing_id' => $r->file_indexing_id])->assertSessionHasNoErrors();
        $other = $this->intakeFile(['file_number' => 'COM-2016-10'], 'conversion');
        $this->post(route('kamma.bills.store'), ['bill_type' => 'processing', 'file_indexing_id' => $other->id])->assertSessionHasNoErrors();
        [$a, $b] = Bill::query()->orderBy('id')->get()->all();

        $this->get(route('kamma.payments.index'))->assertOk()->assertSee($a->bill_no)->assertSee('Record payment');
        $this->get(route('kamma.payments.index', ['from' => 'revm', 'tab' => 'awaiting']))->assertOk()->assertSee('Awaiting Receipts');

        $pay = ['receipt_no' => 'rct-001', 'amount_paid' => '4000', 'paid_on' => '2026-11-24', 'channel' => 'Bank', 'bill_id' => $a->id];
        $this->post(route('kamma.payments.store', $a->id), $pay)->assertSessionHasErrorsIn('payment', 'amount_paid');
        $this->post(route('kamma.payments.store', $a->id), ['amount_paid' => '5000', 'channel' => 'Other'] + $pay)->assertSessionHasErrorsIn('payment', 'channel_other');
        $this->post(route('kamma.payments.store', $a->id), ['amount_paid' => '5000'] + $pay)->assertSessionHasNoErrors();

        $a->refresh();
        $this->assertSame('paid', $a->status);
        $this->assertSame('RCT-001', $a->receipt_no);
        $this->assertSame('2026-11-24', $a->paid_on->toDateString());

        $this->post(route('kamma.payments.store', $a->id), ['amount_paid' => '5000', 'receipt_no' => 'RCT-002'] + $pay)->assertSessionHasErrorsIn('payment', 'receipt_no');
        $this->post(route('kamma.payments.store', $b->id), ['amount_paid' => '5000', 'bill_id' => $b->id] + $pay)->assertSessionHasErrorsIn('payment', 'receipt_no');

        $this->get(route('kamma.payments.receipt', $a->id))->assertOk()->assertSee('RCT-001')->assertSee('₦5,000.00');
        $this->get(route('kamma.payments.receipt', $b->id))->assertNotFound();
        $this->get(route('kamma.bills.print', $b->id))->assertOk()->assertSee($b->bill_no)->assertSee('Application Processing Fee Bill');
        $this->get(route('kamma.payments.receipts'))->assertOk()->assertSee('RCT-001')->assertDontSee($b->bill_no);

        $this->get(route('kamma.payments.report'))->assertOk()->assertSee('₦5,000.00')->assertSee('Nov 2026');
        $csv = $this->get(route('kamma.payments.report.csv'))->assertOk()->streamedContent();
        $this->assertStringContainsString($a->bill_no, $csv);
        $this->assertStringContainsString('RCT-001', $csv);
        $this->assertStringNotContainsString($b->bill_no, $csv);
    }

    public function test_title_status_is_recorded_from_the_klaes_land_file(): void
    {
        $id = $this->klaesFile();
        $this->actingAs($this->officer);
        $this->get(route('kamma.title-status.index'))->assertOk()->assertSee('No title status recorded yet');

        $base = ['klaes_file_indexing_id' => $id, 'reason' => 'Suit  FHC/KN/123 pending'];
        $this->post(route('kamma.title-status.store'), $base + ['title_type' => 'Litigation', 'initiated_by' => 'Ministry'])->assertSessionHasErrors('initiated_by');
        $this->post(route('kamma.title-status.store'), $base + ['title_type' => 'Other', 'initiated_by' => 'Court Order'])->assertSessionHasErrors('title_type_other');
        $this->post(route('kamma.title-status.store'), $base + ['title_type' => 'Litigation', 'initiated_by' => 'Court Order'])->assertSessionHasNoErrors();

        $t = TitleStatusUpdate::query()->sole();
        $this->assertSame('RES-2016-452', $t->file_number);
        $this->assertSame("ISMA'ILA HABU", $t->file_title);
        $this->assertSame(['3075', 'WESTERN BY- PASS', 'Kumbotso'], [$t->plot_number, $t->district, $t->lga]);
        $this->assertSame('Suit FHC/KN/123 pending', $t->reason);
        $this->assertSame('Litigation FileNo RES-2016-452 was initiated by Court Order on 25/11/2026 10:00 due to Suit FHC/KN/123 pending', $t->remark);

        $this->post(route('kamma.title-status.store'), $base + ['title_type' => 'Litigation', 'initiated_by' => 'Court Order'])->assertSessionHasErrors('title_type');
        $this->post(route('kamma.title-status.store'), ['klaes_file_indexing_id' => 999999, 'reason' => 'x', 'title_type' => 'Surrender', 'initiated_by' => 'Applicant'])->assertSessionHasErrors('klaes_file_indexing_id');

        $this->get(route('kamma.title-status.index', ['q' => 'RES-2016']))->assertOk()->assertSee('Litigation')->assertSee('Court Order');
    }
}
