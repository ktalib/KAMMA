<?php

namespace Tests\Feature\Bpa;

use App\Models\Bpa\Application;
use App\Models\User;
use App\Services\Bpa\CommissionBpFile;
use App\Services\Bpa\RecordPayment;
use App\Services\Bpa\SubmitApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * Director (Arch.) then Deputy Director (Arch.) recommendation, the KLAES Cadastral way: Recommend moves the
 * file on; Not recommended needs a reason and sends it back one desk.
 */
class DeskTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 22));
        $this->officer = $this->klaesUser(['rank' => 'Director (Arch.)']);
    }

    private function commissioned(string $receipt = 'R1'): Application
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
        ], [UploadedFile::fake()->create('booklet.pdf', 100)], $this->officer);
        app(RecordPayment::class)->handle($application->bill, [
            'receipt_no' => $receipt, 'paid_on' => '2026-11-22', 'amount_paid' => 15000, 'payment_channel' => 'Bank',
        ], $this->officer);

        return app(CommissionBpFile::class)->handle($application, $this->officer);
    }

    private function decide(string $desk, Application $application, string $decision, ?string $remarks = null)
    {
        return $this->actingAs($this->officer)->post(route('bpa.desks.decide', [$desk, $application]), array_filter([
            'application_id' => $application->id, 'decision' => $decision, 'remarks' => $remarks,
        ]));
    }

    public function test_a_commissioned_file_goes_to_the_director(): void
    {
        $application = $this->commissioned();

        $this->assertSame('director_arch', $application->stage);
        $this->assertSame('With Director (Arch.)', $application->status);
        $this->assertSame(['commissioned'], $application->movements()->pluck('action')->all());

        $this->actingAs($this->officer)->get('/development-permit/approvals/director')
            ->assertOk()->assertSee('BP-2026-1')->assertSee('Recommend / Not recommend');
        $this->actingAs($this->officer)->get('/development-permit/approvals/deputy-director')
            ->assertOk()->assertDontSee('BP-2026-1');
    }

    public function test_director_then_deputy_director_recommend_and_the_file_reaches_the_zonal_office(): void
    {
        $application = $this->commissioned();

        $this->decide('director', $application, 'recommended', 'Plans conform.')->assertSessionHasNoErrors();
        $this->assertSame('dd_arch', $application->fresh()->stage);
        $this->flushSession();

        $this->actingAs($this->officer)->get('/development-permit/approvals/director?tab=decided')
            ->assertOk()->assertSee('BP-2026-1')->assertSee('Recommended')->assertSee('Plans conform.');
        $this->actingAs($this->officer)->get('/development-permit/approvals/deputy-director')->assertSee('BP-2026-1');

        $this->decide('deputy-director', $application, 'recommended')->assertSessionHasNoErrors();
        $this->assertSame('zonal_office', $application->fresh()->stage);
        $this->assertSame('With Zonal Office', $application->fresh()->status);
    }

    public function test_not_recommended_needs_a_reason_and_returns_the_file_to_architecture(): void
    {
        $application = $this->commissioned();

        $this->decide('director', $application, 'not_recommended')->assertSessionHasErrors('remarks', null, 'decision');
        $this->assertSame('director_arch', $application->fresh()->stage);

        $this->decide('director', $application, 'not_recommended', 'Setback to the street is short.')->assertSessionHasNoErrors();
        $this->assertSame('architecture', $application->fresh()->stage);
        $this->assertSame('Returned to Architecture', $application->fresh()->status);
        $this->flushSession();

        $this->actingAs($this->officer)->get('/development-permit/architecture/building-plans?returned=1')
            ->assertOk()->assertSee('Returned')->assertSee('Setback to the street is short.')->assertSee('Send back to Director (Arch.)');

        $this->actingAs($this->officer)->post(route('bpa.building-plans.resend', $application), ['remarks' => 'Revised site plan uploaded.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('director_arch', $application->fresh()->stage);
        $this->flushSession();

        $this->actingAs($this->officer)->get('/development-permit/approvals/director')->assertSee('Re-sent after correction');
    }

    public function test_the_deputy_director_returns_a_file_to_the_director(): void
    {
        $application = $this->commissioned();
        $this->decide('director', $application, 'recommended');

        $this->decide('deputy-director', $application, 'not_recommended', 'Fire service report missing.')->assertSessionHasNoErrors();

        $this->assertSame('director_arch', $application->fresh()->stage);
        $this->flushSession();
        $this->actingAs($this->officer)->get('/development-permit/approvals/director')->assertSee('Returned by Deputy Director (Arch.)');
    }

    public function test_a_desk_cannot_decide_a_file_that_is_not_on_it(): void
    {
        $application = $this->commissioned();

        $this->decide('deputy-director', $application, 'recommended')->assertSessionHasErrors('application', null, 'decision');
        $this->assertSame('director_arch', $application->fresh()->stage);

        $this->decide('director', $application, 'recommended');
        $this->decide('director', $application, 'recommended')->assertSessionHasErrors('application', null, 'decision');
        $this->assertSame('dd_arch', $application->fresh()->stage);

        $this->actingAs($this->officer)->post(route('bpa.building-plans.resend', $application))->assertSessionHasErrors('application', null, 'resend');
    }

    public function test_the_application_page_shows_the_file_movement(): void
    {
        $application = $this->commissioned();
        $this->decide('director', $application, 'not_recommended', 'Setback to the street is short.');
        $this->actingAs($this->officer)->post(route('bpa.building-plans.resend', $application), ['remarks' => 'Revised site plan uploaded.']);
        $this->decide('director', $application, 'recommended');
        $this->flushSession();

        $this->actingAs($this->officer)->get(route('bpa.applications.show', $application))
            ->assertOk()
            ->assertSee('Now with:')
            ->assertSeeInOrder([
                'Commissioned and sent to the Director (Arch.)',
                'Not recommended by the Director (Arch.), returned to Architecture',
                'Setback to the street is short.',
                'Corrected and sent back to the Director (Arch.)',
                'Recommended by the Director (Arch.), sent to the Deputy Director (Arch.)',
            ]);
    }

    public function test_the_sidebar_follows_the_kadca_menu_document(): void
    {
        $response = $this->actingAs($this->officer)->get('/development-permit/approvals/director');

        $response->assertOk()->assertSeeInOrder([
            'Dashboard', "MD&#039;s View", 'Customer Relationship Management', 'DMS', 'Digital File Archive', 'File Tracking',
            'Information Products', 'KLAES REV-M', 'Land Records', 'KAMMA', 'KAMMA', 'GIS', 'Legacy Systems', 'HR Manager', 'System Admin',
        ], false);
        $response->assertSee('Bulk SMS Management')->assertSee('Head of Zonal Office')->assertSee('Final Conveyance');
        $response->assertSee('aria-current="page"', false);

        $this->actingAs($this->officer)->get(route('not-built', ['item' => 'KAMMA › Development Permit › Final Conveyance']))
            ->assertOk()->assertSee('Not built yet')->assertSee('KAMMA › Development Permit › Final Conveyance');
    }
}
