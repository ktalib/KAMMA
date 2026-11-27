<?php

namespace Tests\Feature\Bpa;

use App\Models\Bpa\Application;
use App\Models\User;
use App\Services\Bpa\SubmitApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * Architecture: the application bill is confirmed paid, then the BP file is commissioned as
 * BP-[Year]-[Serial] (user 2026-11-22: "it should be BP-2026-1"), numbering restarting on 1 January.
 */
class ArchitectureTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 22));
        $this->officer = $this->klaesUser(['rank' => 'Architect I']);
    }

    private function application(int $plots = 1): Application
    {
        return app(SubmitApplication::class)->handle([
            'klaes_file_indexing_id' => $this->klaesFile(),
            'proposed_use' => 'Residential',
            'developer_name' => "ISMA'ILA HABU",
            'contact_address' => 'NO 5 ZOO ROAD, KANO',
            'developer_phone' => '08031234567',
            'submission_type' => 'New Submission',
            'plan_received_as' => 'Scanned at the counter',
            'number_of_plots' => $plots,
        ], [UploadedFile::fake()->create('booklet.pdf', 100)], $this->officer)->load('bill');
    }

    private function pay(Application $application, array $overrides = [])
    {
        return $this->actingAs($this->officer)->post(route('bpa.payments.store', $application->bill), array_merge([
            'bill_id' => $application->bill->id,
            'receipt_no' => 'rcp-0001',
            'paid_on' => '2026-11-22',
            'amount_paid' => '15,000.00',
            'payment_channel' => 'Bank',
        ], $overrides));
    }

    public function test_a_payment_is_recorded_against_the_bill(): void
    {
        $application = $this->application();

        $this->pay($application)->assertRedirect(route('bpa.payments.index', ['tab' => 'unpaid']))->assertSessionHasNoErrors();

        $bill = $application->bill->fresh();
        $this->assertSame('Paid', $bill->status);
        $this->assertSame('RCP-0001', $bill->receipt_no);
        $this->assertSame('15000.00', $bill->amount_paid);
        $this->assertSame('Amina Bello', $bill->payment_recorded_by_name);
        $this->assertSame('Paid, awaiting commissioning', $application->fresh()->status);
    }

    public function test_a_wrong_amount_a_reused_receipt_or_a_second_payment_is_refused(): void
    {
        $first = $this->application();
        $second = $this->application(2);

        $this->pay($first, ['amount_paid' => '10000'])->assertSessionHasErrors('amount_paid', null, 'payment');
        $this->assertSame('Unpaid', $first->bill->fresh()->status);

        $this->pay($first)->assertSessionHasNoErrors();
        $this->pay($second, ['receipt_no' => 'RCP-0001', 'amount_paid' => '30000'])->assertSessionHasErrors('receipt_no', null, 'payment');
        $this->pay($first, ['receipt_no' => 'RCP-0002'])->assertSessionHasErrors('receipt_no', null, 'payment');
        $this->assertSame('RCP-0001', $first->bill->fresh()->receipt_no);
    }

    public function test_other_channel_needs_specifying(): void
    {
        $application = $this->application();

        $this->pay($application, ['payment_channel' => 'Other'])->assertSessionHasErrors('payment_channel_other', null, 'payment');
        $this->pay($application, ['payment_channel' => 'Other', 'payment_channel_other' => 'remita'])->assertSessionHasNoErrors();
        $this->assertSame('REMITA', $application->bill->fresh()->payment_channel_other);
    }

    public function test_the_payment_page_lists_unpaid_and_paid_bills(): void
    {
        $paid = $this->application();
        $this->application(2);
        $this->pay($paid);
        $this->flushSession(); // the "Payment recorded on …" message would name the paid bill

        $this->actingAs($this->officer)->get('/development-permit/architecture/payments')
            ->assertOk()->assertSee('BPA-BILL/2026/00002')->assertDontSee('BPA-BILL/2026/00001')->assertSee('Record payment');
        $this->actingAs($this->officer)->get('/development-permit/architecture/payments?tab=paid')
            ->assertOk()->assertSee('BPA-BILL/2026/00001')->assertSee('RCP-0001')->assertDontSee('BPA-BILL/2026/00002');
    }

    public function test_an_unpaid_application_cannot_be_commissioned(): void
    {
        $application = $this->application();

        $this->actingAs($this->officer)->post(route('bpa.commissioning.store', $application))->assertSessionHasErrors('application');

        $this->assertNull($application->fresh()->bp_file_no);
    }

    public function test_paid_applications_get_bp_numbers_that_restart_each_year(): void
    {
        $a = $this->application();
        $b = $this->application();
        $c = $this->application();
        $this->pay($a, ['receipt_no' => 'R1']);
        $this->pay($b, ['receipt_no' => 'R2']);
        $this->pay($c, ['receipt_no' => 'R3']);

        $this->actingAs($this->officer)->post(route('bpa.commissioning.store', $a))->assertSessionHasNoErrors();
        $this->actingAs($this->officer)->post(route('bpa.commissioning.store', $b))->assertSessionHasNoErrors();
        $this->travelTo(now()->setDate(2027, 1, 1));
        $this->actingAs($this->officer)->post(route('bpa.commissioning.store', $c))->assertSessionHasNoErrors();

        $this->assertSame('BP-2026-1', $a->fresh()->bp_file_no);
        $this->assertSame('BP-2026-2', $b->fresh()->bp_file_no);
        $this->assertSame('BP-2027-1', $c->fresh()->bp_file_no);
        $this->assertSame('With Director (Arch.)', $a->fresh()->status);
        $this->assertSame('Amina Bello', $a->fresh()->commissioned_by_name);
    }

    public function test_a_file_is_commissioned_once(): void
    {
        $application = $this->application();
        $this->pay($application);
        $this->actingAs($this->officer)->post(route('bpa.commissioning.store', $application));

        $this->actingAs($this->officer)->post(route('bpa.commissioning.store', $application))->assertSessionHasErrors('application');

        $this->assertSame('BP-2026-1', $application->fresh()->bp_file_no);
        $this->assertSame(1, Application::whereNotNull('bp_file_no')->count());
    }

    public function test_the_commissioning_and_building_plan_lists(): void
    {
        $waiting = $this->application();
        $unpaid = $this->application();
        $done = $this->application();
        $this->pay($waiting, ['receipt_no' => 'R1']);
        $this->pay($done, ['receipt_no' => 'R2']);
        $this->actingAs($this->officer)->post(route('bpa.commissioning.store', $done));
        $this->flushSession(); // the "… commissioned as …" message would name the commissioned application

        $this->actingAs($this->officer)->get('/development-permit/architecture/commissioning')
            ->assertOk()
            ->assertSee($waiting->application_no)
            ->assertDontSee($unpaid->application_no)
            ->assertDontSee($done->application_no)
            ->assertSee('Commission BP file');

        $this->actingAs($this->officer)->get('/development-permit/architecture/commissioning?tab=commissioned')
            ->assertOk()->assertSee('BP-2026-1')->assertDontSee($waiting->application_no);

        $this->actingAs($this->officer)->get('/development-permit/architecture/building-plans')
            ->assertOk()->assertSee('BP-2026-1')->assertSee('1 plan')->assertDontSee($waiting->application_no);

        $this->actingAs($this->officer)->get(route('bpa.applications.show', $done))
            ->assertOk()->assertSee('BP-2026-1')->assertSee('R2');
    }

    public function test_the_sidebar_carries_the_logo_and_the_architecture_section(): void
    {
        $this->actingAs($this->officer)->get('/dashboard')
            ->assertOk()
            ->assertSee('images/logo.png', false)
            ->assertSee('KLAES-METRO')
            ->assertSeeInOrder(['Assessment Stage', 'Application Form', 'Initial Bill', 'Architecture', 'Building Plan', 'Payment', 'File Commissioning']);
    }
}
