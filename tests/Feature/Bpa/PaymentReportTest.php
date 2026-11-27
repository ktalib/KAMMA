<?php

namespace Tests\Feature\Bpa;

use App\Models\User;
use App\Services\Bpa\RecordPayment;
use App\Services\Bpa\SubmitApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/** Bills & Payments > Payments Report: totals, breakdowns, filters and the CSV, over KAMMA bills only. */
class PaymentReportTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 22));
        $this->officer = $this->klaesUser();

        // Three payments (Oct Bank ₦15k, Nov POS ₦30k, Nov Other ₦15k) and one unpaid bill (₦15k).
        foreach ([['2026-10-05', 'Bank', 1, 'RCP-OCT'], ['2026-11-10', 'POS', 2, 'RCP-NOV1'], ['2026-11-20', 'Other', 1, 'RCP-NOV2'], [null, null, 1, null]] as $i => [$paidOn, $channel, $plots, $receipt]) {
            $application = app(SubmitApplication::class)->handle([
                'klaes_file_indexing_id' => $this->klaesFile(), 'proposed_use' => 'Residential', 'developer_name' => 'DEVELOPER '.($i + 1),
                'contact_address' => 'KANO', 'developer_phone' => '08031234567', 'submission_type' => 'New Submission',
                'plan_received_as' => 'Scanned at the counter', 'number_of_plots' => $plots,
            ], [UploadedFile::fake()->create('p.pdf', 10)], $this->officer);
            if ($paidOn) {
                app(RecordPayment::class)->handle($application->bill, [
                    'receipt_no' => $receipt, 'paid_on' => $paidOn, 'amount_paid' => 15000 * $plots,
                    'payment_channel' => $channel, 'payment_channel_other' => $channel === 'Other' ? 'REMITA' : null,
                ], $this->officer);
            }
        }
    }

    public function test_the_report_totals_and_breaks_down_the_payments(): void
    {
        $this->actingAs($this->officer)->get('/development-permit/payments-report')
            ->assertOk()
            ->assertSee('Payments Report')
            ->assertSee('₦60,000.00')                 // total paid
            ->assertSee('3 / 1')                      // paid / unpaid bills
            ->assertSee('BPA Application Fee')
            ->assertSee('Other: REMITA')
            ->assertSeeInOrder(['RCP-NOV2', 'RCP-NOV1', 'RCP-OCT'])  // newest first
            ->assertSee('Nov 2026')
            ->assertSee('chart.js', false);
    }

    public function test_the_report_filters_by_date_channel_and_search(): void
    {
        $this->actingAs($this->officer)->get('/development-permit/payments-report?from=2026-11-01&to=2026-11-30')
            ->assertSee('₦45,000.00')->assertSee('RCP-NOV1')->assertSee('RCP-NOV2')->assertDontSee('RCP-OCT')
            ->assertSee('1 Nov 2026 to 30 Nov 2026');

        $this->actingAs($this->officer)->get('/development-permit/payments-report?channel=POS')
            ->assertSee('RCP-NOV1')->assertDontSee('RCP-NOV2')->assertDontSee('RCP-OCT');

        $this->actingAs($this->officer)->get('/development-permit/payments-report?search=developer 1')
            ->assertSee('RCP-OCT')->assertDontSee('RCP-NOV1');
    }

    public function test_the_csv_holds_the_filtered_payments(): void
    {
        $response = $this->actingAs($this->officer)->get('/development-permit/payments-report.csv?from=2026-11-01');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertCount(3, $lines); // header + 2 November payments
        $this->assertStringContainsString('S/N,"Receipt No."', $lines[0]);
        $this->assertStringContainsString('RCP-NOV1,2026-11-10,30000.00,POS', $lines[1]);
        $this->assertStringContainsString('"Other: REMITA"', $lines[2]);
        $this->assertStringNotContainsString('RCP-OCT', $csv);
    }

    public function test_the_menu_links_the_report(): void
    {
        $html = $this->actingAs($this->officer)->get('/development-permit/payments-report')->getContent();

        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('bpa.payments-report'), '#').'"\s+aria-current="page"#', $html);
    }
}
