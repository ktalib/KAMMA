<?php

namespace Tests\Feature\Auth;

use App\Services\BulkSmsNgService;
use App\Services\LoginOtpService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * The sign-in code (ported from KLAES): SMS to a confirmed mobile, email to a real address, switch between them,
 * wrong codes, cooldown, and no write to the KLAES account. No real SMS or email leaves: the gateway is replaced by a
 * recorder and mail uses the array transport.
 */
class LoginOtpTest extends TestCase
{
    use KlaesUsers;

    /** @var list<array{phone:string, message:string}> */
    private array $texts = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'array', 'cache.default' => 'array']);
        // Daytime, so the first code goes by SMS (between 19:45 and 08:00 email goes first).
        Carbon::setTestNow(Carbon::parse('2026-11-23 10:00', 'Africa/Lagos'));

        $texts = &$this->texts;
        $this->app->instance(BulkSmsNgService::class, new class($texts) extends BulkSmsNgService
        {
            public function __construct(private array &$sent)
            {
            }

            public function send(string $phone, string $message, ?string $sender = null): bool
            {
                $this->sent[] = ['phone' => $phone, 'message' => $message];

                return true;
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function confirmedUser(array $attributes = [])
    {
        return $this->klaesUser(array_merge([
            'phone_number' => '08031234567',
            'email' => 'amina.bello@gmail.com',
            'is_otp_verified' => 1,
            'otp_channel' => 'both',
        ], $attributes));
    }

    private function codeFromText(): string
    {
        $this->assertNotEmpty($this->texts, 'No SMS was sent.');
        preg_match('/\b(\d{6})\b/', end($this->texts)['message'], $m);

        return $m[1];
    }

    private function codeFromEmail(): string
    {
        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertGreaterThan(0, $messages->count(), 'No email was sent.');
        preg_match('/\b(\d{6})\b/', $messages[$messages->count() - 1]->getOriginalMessage()->getTextBody(), $m);

        return $m[1];
    }

    public function test_a_confirmed_mobile_gets_the_code_by_sms_and_the_code_signs_in(): void
    {
        $user = $this->confirmedUser();

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass'])->assertRedirect(route('login.otp'));
        $this->assertGuest();
        $this->assertSame('2348031234567', $this->texts[0]['phone']);
        $this->assertStringContainsString('KLAES-METRO sign-in', $this->texts[0]['message']);

        $this->get(route('login.otp'))->assertOk()->assertSee('We texted')->assertSee('0803****567')->assertSee('Email it to me instead');

        $this->post(route('login.otp.verify'), ['code' => $this->codeFromText()])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_user_can_switch_to_email_and_sign_in_with_the_emailed_code(): void
    {
        $user = $this->confirmedUser();
        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);

        $this->post(route('login.otp.resend'), ['channel' => 'email'])->assertRedirect(route('login.otp'))->assertSessionHas('success');
        $this->get(route('login.otp'))->assertSee('We emailed')->assertSee('am***@gmail.com')->assertSee('Text it to me instead');

        $this->post(route('login.otp.verify'), ['code' => $this->codeFromEmail()]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_an_account_without_a_confirmed_mobile_gets_the_code_by_email_and_is_never_texted(): void
    {
        $this->confirmedUser(['is_otp_verified' => 0]);

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass'])->assertRedirect(route('login.otp'));

        $this->assertSame([], $this->texts);
        $this->get(route('login.otp'))->assertSee('We emailed')->assertDontSee('Text it to me instead');
        $this->post(route('login.otp.resend'), ['channel' => 'sms'])->assertSessionHas('error');
        $this->assertSame([], $this->texts);
    }

    public function test_between_1945_and_0800_the_first_code_goes_by_email(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-23 21:00', 'Africa/Lagos'));
        $this->confirmedUser();

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);

        $this->assertSame([], $this->texts);
        $this->assertSame(1, app('mailer')->getSymfonyTransport()->messages()->count());
    }

    public function test_accounts_with_no_mobile_and_only_a_placeholder_email_sign_in_with_the_password_alone(): void
    {
        $user = $this->klaesUser(['email' => 'someone@example.com']);

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_klaes_exempt_accounts_skip_the_code(): void
    {
        $user = $this->confirmedUser(['email' => 'KatSolTech@gmail.com']);

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);

        $this->assertAuthenticatedAs($user);
        $this->assertSame([], $this->texts);
    }

    public function test_wrong_codes_are_counted_and_the_fifth_ends_the_sign_in(): void
    {
        $this->confirmedUser();
        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);
        $good = $this->codeFromText();
        $bad = $good === '000000' ? '111111' : '000000';

        $this->post(route('login.otp.verify'), ['code' => $bad])->assertRedirect(route('login.otp'))->assertSessionHas('error', 'The code is incorrect. 4 tries left.');
        foreach (range(1, 3) as $i) {
            $this->post(route('login.otp.verify'), ['code' => $bad]);
        }
        $this->post(route('login.otp.verify'), ['code' => $bad])->assertRedirect(route('login'))->assertSessionHas('error', 'Too many incorrect codes. Sign in again.');

        $this->post(route('login.otp.verify'), ['code' => $good])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_resend_waits_for_the_cooldown_and_a_repeat_sign_in_reuses_the_code(): void
    {
        $this->confirmedUser();
        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);

        $this->post(route('login.otp.resend'), ['channel' => 'sms'])->assertSessionHas('error', fn ($m) => str_starts_with($m, 'Wait '));
        $this->assertCount(1, $this->texts);

        // Signing in again a moment later does not text a second code.
        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass'])->assertSessionHas('success', fn ($m) => str_contains($m, 'a moment ago'));
        $this->assertCount(1, $this->texts);

        Carbon::setTestNow(now()->addSeconds(61));
        $this->post(route('login.otp.resend'), ['channel' => 'sms'])->assertSessionHas('success');
        $this->assertCount(2, $this->texts);
    }

    public function test_the_code_screen_needs_a_pending_sign_in_and_cancel_clears_it(): void
    {
        $this->get(route('login.otp'))->assertRedirect(route('login'));

        $this->confirmedUser();
        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);
        $this->post(route('login.otp.cancel'))->assertRedirect(route('login'));
        $this->get(route('login.otp'))->assertRedirect(route('login'));
    }

    public function test_the_klaes_account_is_never_written(): void
    {
        $this->confirmedUser();
        $before = (array) DB::connection('klaes')->table('users')->first();

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);
        $this->post(route('login.otp.verify'), ['code' => $this->codeFromText()]);

        $this->assertAuthenticated();
        $this->assertSame($before, (array) DB::connection('klaes')->table('users')->first());
    }

    public function test_the_real_gateway_refuses_to_send_from_the_console(): void
    {
        $gateway = new BulkSmsNgService();

        $this->assertFalse($gateway->send('08031234567', 'test'));
        $this->assertStringContainsString('console', (string) $gateway->lastFailureReason());
        $this->assertSame('2348031234567', BulkSmsNgService::normalizeNumber('0803 123 4567'));
        $this->assertNull(app(LoginOtpService::class)->emailFor($this->klaesUser(['username' => 'x', 'email' => 'a@b.local'])));
    }
}
