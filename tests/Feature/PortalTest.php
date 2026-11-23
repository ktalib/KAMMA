<?php

namespace Tests\Feature;

use App\Support\Portal\PortalSession;
use App\Support\Portal\PortalTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * The KAMMA applicant portal (UI first): the public landing page, its own sign-in, the demo applicant, every screen
 * and the preview saves (nothing stored).
 */
class PortalTest extends TestCase
{
    use KlaesFiles, KlaesUsers, RefreshDatabase;

    private function asDemo(): static
    {
        return $this->withSession([PortalSession::KEY => [
            'username' => 'demo.applicant', 'name' => 'Demo Applicant', 'phone' => '08000000000', 'email' => null,
            'is_demo' => true, 'since' => now()->toDateTimeString(),
        ]]);
    }

    public function test_guests_get_the_landing_page_with_sign_in_and_sign_up(): void
    {
        $response = $this->get('/portal')->assertOk()
            ->assertSee('KLAES-METRO')
            ->assertSee('Applicant Portal')
            ->assertSee('Kano State Metropolitan Area Management Agency')
            ->assertSee('images/landing/kano-1.webp', false)
            ->assertSee('href="'.route('portal.login').'"', false)
            ->assertSee('href="'.route('portal.register').'"', false)
            ->assertSee('Sign in')
            ->assertSee('Create account')
            ->assertSee('app-preloader', false)
            ->assertSee("localStorage.getItem('theme')", false);

        foreach (['What you can do', 'How your application moves', 'What you need', 'What it costs', 'Frequently asked', 'Talk to KAMMA',
            'Shadow File commissioned', 'Zonal KSI', 'MD KAMMA', 'Returned to the Ministry', 'Sub-Division', 'Change of Purpose', 'Standard × 4'] as $text) {
            $response->assertSee($text);
        }
        $response->assertDontSee('KADCA')->assertDontSee('Building plan application fee');

        // The sign-in and register pages lead back to the landing page.
        $this->get('/portal/login')->assertOk()->assertSee('Back to home')->assertSee('href="'.route('portal.home').'"', false)
            ->assertSee('KLAES-METRO')->assertSee('Sign in as the demo applicant')->assertSee('demo.applicant');
        $this->get('/portal/register')->assertOk()->assertSee('Back to home')->assertSee('Mobile number');
    }

    public function test_a_signed_in_applicant_on_the_landing_page_goes_to_the_dashboard(): void
    {
        $this->asDemo()->get('/portal')->assertRedirect(route('portal.dashboard'));
        $this->asDemo()->get('/portal/login')->assertRedirect(route('portal.dashboard'));
    }

    public function test_guests_are_sent_to_the_portal_sign_in_not_the_staff_login(): void
    {
        foreach (['/portal/dashboard', '/portal/applications/create', '/portal/bills', '/portal/notifications', '/portal/profile'] as $url) {
            $this->get($url)->assertRedirect(route('portal.login'));
        }
        $this->getJson('/portal/land-files/search?q=RES')->assertUnauthorized();
    }

    public function test_the_demo_applicant_signs_in_by_button_or_password_and_a_wrong_password_is_refused(): void
    {
        $this->post('/portal/demo')->assertRedirect(route('portal.dashboard'));
        $this->assertTrue(session(PortalSession::KEY)['is_demo']);

        $this->post('/portal/logout')->assertRedirect(route('portal.login'));
        $this->assertNull(session(PortalSession::KEY));

        $this->post('/portal/login', ['login' => 'demo.applicant', 'password' => 'wrong'])->assertSessionHasErrors('login');
        $this->post('/portal/login', ['login' => 'Demo.Applicant', 'password' => 'kamma-demo'])->assertRedirect(route('portal.dashboard'));
    }

    public function test_register_confirms_the_phone_and_signs_in_for_the_session_only(): void
    {
        $this->post('/portal/register', ['name' => '  aminu   bello ', 'phone' => '08031234567', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1'])
            ->assertRedirect(route('portal.verify'));
        $pending = session(PortalSession::PENDING);
        $this->post('/portal/register/verify', ['code' => $pending['code']])->assertRedirect(route('portal.dashboard'));

        $this->get('/portal/dashboard')->assertOk()->assertSee('Welcome, Aminu Bello')->assertSee('You have not made a request yet');
    }

    public function test_every_portal_page_renders_for_the_demo_applicant(): void
    {
        $screens = [
            '/portal/dashboard' => ['My requests', 'DEMO-KPR/2026/00005', 'Track a request', 'Preview'],
            '/portal/applications/create' => ['New request for a Planning Recommendation', 'Type of request', 'Your land file', 'Building on the land', 'Fees &amp; review', 'Submit request'],
            '/portal/bills' => ['Bills', 'DEMO-KBB/2026/00003', 'Application Processing Fee', 'S/N', 'aria-label="Actions"', '×4 penalty'],
            '/portal/notifications' => ['Notifications', 'Shadow File commissioned', 'KSI completed'],
            '/portal/profile' => ['My profile', 'demo.applicant', 'Change password'],
            '/portal/applications/DEMO-KPR/2026/00005' => ['Sub-Division', 'Awaiting your payment'],
            '/portal/applications/DEMO-KPR/2026/00004' => ['Now: Zonal KSI', 'geocoded photos'],
            '/portal/applications/DEMO-KPR/2026/00003' => ['No-BPA penalty', 'Standard betterment', '×4', '₦1,000,000.00'],
            '/portal/applications/DEMO-KPR/2026/00002' => ['Now: MD KAMMA', 'COM-DEMO-0012'],
            '/portal/applications/DEMO-KPR/2026/00001' => ['Done.', 'Planning Recommendation', 'Not recommended by the MD KAMMA'],
        ];

        foreach ($screens as $url => $texts) {
            $response = $this->asDemo()->get($url)->assertOk();
            foreach ($texts as $text) {
                $response->assertSee($text, false);
            }
            // Both themes, the preloader and KLAES-METRO branding on every screen.
            $response->assertSee("localStorage.getItem('theme')", false)
                ->assertSee('dark:bg-slate-950', false)
                ->assertSee('app-preloader', false)
                ->assertSee('KLAES-METRO')
                ->assertDontSee('KLAES-CONTROL');
        }
    }

    public function test_the_request_page_shows_the_kamma_timeline_in_order_and_property_order(): void
    {
        $this->asDemo()->get('/portal/applications/DEMO-KPR/2026/00003')->assertOk()
            ->assertSeeInOrder(['Request received', 'Shadow File commissioned', 'Processing fee', 'Site visit', 'Zonal KSI', 'Betterment bill', 'Planning Recommendation', 'MD KAMMA', 'Returned to the Ministry'])
            ->assertSeeInOrder(['Plot No.', 'District', 'LGA', 'Location']);

        $this->asDemo()->get('/portal/applications/DEMO-KPR/2026/09999')->assertNotFound();
        $this->asDemo()->get('/portal/applications/track?number=demo-kpr/2026/00002')->assertRedirect(route('portal.applications.show', 'DEMO-KPR/2026/00002'));
        $this->asDemo()->from('/portal/dashboard')->get('/portal/applications/track?number=NOPE-1')->assertRedirect('/portal/dashboard')->assertSessionHasErrors('number');
    }

    public function test_the_dashboard_figures_follow_the_demo_requests(): void
    {
        $html = $this->asDemo()->get('/portal/dashboard')->assertOk()->getContent();

        preg_match_all('/data-figure="([^"]+)">(\d+)</', $html, $m);
        $this->assertSame(['My requests' => 5, 'Awaiting payment' => 2, 'In progress' => 2, 'Returned to the Ministry' => 1], array_combine($m[1], array_map('intval', $m[2])));
    }

    public function test_the_land_file_selector_matches_file_numbers_only(): void
    {
        $id = $this->klaesFile();

        $this->asDemo()->getJson('/portal/land-files/search?q=res-2016')->assertOk()->assertJsonPath('files.0.id', $id)->assertJsonMissingPath('files.0.phone');
        $this->asDemo()->getJson('/portal/land-files/'.$id)->assertOk()->assertJsonPath('file.file_number', 'RES-2016-452')->assertJsonMissingPath('file.address');
        $this->asDemo()->getJson('/portal/land-files/999999')->assertNotFound();
    }

    public function test_submitting_a_request_is_checked_and_saves_nothing(): void
    {
        $this->asDemo()->post('/portal/applications', [])
            ->assertSessionHasErrors(['category', 'request_type', 'klaes_file_indexing_id', 'building_exists', 'applicant_name', 'phone', 'files', 'declaration']);

        $base = [
            'category' => 'post_cofo', 'klaes_file_indexing_id' => $this->klaesFile(), 'building_exists' => 'yes', 'has_bpa' => 'no',
            'applicant_name' => 'isma\'ila   habu', 'contact_address' => 'no 5 zoo road', 'phone' => '08031234567',
            'files' => [UploadedFile::fake()->create('cofo.pdf', 10)], 'declaration' => '1',
        ];
        // Type-specific fields.
        $this->asDemo()->post('/portal/applications', ['request_type' => 'Sub-Division'] + $base)->assertSessionHasErrors('proposed_plots');
        $this->asDemo()->post('/portal/applications', ['request_type' => 'Merger'] + $base)->assertSessionHasErrors('merge_file_ids');
        $this->asDemo()->post('/portal/applications', ['request_type' => 'Change of Purpose', 'proposed_use' => 'Other'] + $base)->assertSessionHasErrors('proposed_use_other');
        $this->asDemo()->post('/portal/applications', ['request_type' => 'Conversion'] + $base)->assertSessionHasErrors('request_type');
        $this->asDemo()->post('/portal/applications', ['request_type' => 'Sub-Division', 'building_exists' => 'yes', 'has_bpa' => null, 'proposed_plots' => 3] + $base)->assertSessionHasErrors('has_bpa');

        $this->asDemo()->post('/portal/applications', ['request_type' => 'Sub-Division', 'proposed_plots' => 3] + $base)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Saved (preview)') && str_contains($s, "ISMA'ILA HABU") && str_contains($s, '×4'));

        $this->asDemo()->put('/portal/profile', ['name' => 'x'])->assertSessionHasErrors('name', null, 'details');
        $this->asDemo()->put('/portal/profile', ['name' => 'demo applicant'])->assertSessionHas('status');
    }

    public function test_the_timeline_waits_on_unpaid_bills(): void
    {
        $unpaid = PortalTimeline::build(['stage' => 'site_visit', 'bills' => [['kind' => 'processing', 'bill_no' => 'B', 'amount' => 1, 'status' => 'Unpaid', 'receipt_no' => null]], 'events' => []]);
        $this->assertSame('Processing fee', $unpaid['current']);

        $paid = PortalTimeline::build(['stage' => 'ksi', 'bills' => [['kind' => 'processing', 'bill_no' => 'B', 'amount' => 1, 'status' => 'Paid', 'receipt_no' => 'R']], 'events' => []]);
        $this->assertSame('Zonal KSI', $paid['current']);
        $this->assertSame(44, $paid['percent']); // 4 of 9 steps done

        $done = PortalTimeline::build(['stage' => PortalTimeline::COMPLETE, 'bills' => [
            ['kind' => 'processing', 'status' => 'Paid', 'bill_no' => 'B', 'amount' => 1, 'receipt_no' => 'R'],
            ['kind' => 'betterment', 'status' => 'Paid', 'bill_no' => 'C', 'amount' => 1, 'receipt_no' => 'R'],
        ], 'events' => []]);
        $this->assertTrue($done['complete']);
        $this->assertSame(100, $done['percent']);
    }
}
