<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\DB;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/**
 * Sign-in is with KLAES accounts (dbo.users), by username, with KLAES's account checks.
 */
class AuthenticationTest extends TestCase
{
    use KlaesUsers;

    public function test_login_screen_asks_for_the_klaes_username_and_offers_no_self_service(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('name="username"', false);
        $response->assertDontSee('name="email"', false);
        $response->assertDontSee('name="remember"', false);
        $response->assertDontSee('Forgot password');
        $response->assertDontSee('Create one');
    }

    public function test_login_screen_carries_the_agency_logo_and_the_moving_klaes_text(): void
    {
        $response = $this->get('/login');

        $response->assertSee('images/logo.png', false);
        $response->assertSee('Welcome to KLAES-METRO');
        $response->assertSee('Part of the KLAES 2.0 Suite');
        $response->assertSee('id="klaes-suite-scroll"', false);
        $response->assertSeeInOrder(['Kano State Metropolitan Area Management Agency (KAMMA)', 'The KLAES 2.0 Suite Ecosystem']);
        $response->assertSee("localStorage.getItem('theme')", false);
        $response->assertSee('Switch to dark theme', false);
        $response->assertSeeInOrder(['KLAES-CORE (', 'KLAES-GIS (', 'KLAES-METRO (', 'KLAES-CONTROL (', 'KLAES-Rev M (']);
        $response->assertSeeInOrder(['id="klaes-suite-scroll"', 'name="username"'], false);
        $this->assertFileExists(public_path('images/logo.png'));
    }

    public function test_klaes_users_can_sign_in_with_their_username(): void
    {
        $user = $this->klaesUser();

        $response = $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_klaes_users_with_otp_enabled_are_sent_to_the_code_screen(): void
    {
        $this->klaesUser([
            'phone_number' => '08012345678',
            'is_otp_verified' => 1,
        ]);

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass'])
            ->assertRedirect(route('login.otp'));

        $this->assertGuest();
        $this->get(route('login.otp'))
            ->assertOk()
            ->assertSee('Enter your sign-in code')
            ->assertSee('name="code"', false);
    }

    public function test_the_email_address_is_not_a_username(): void
    {
        $this->klaesUser();

        $this->post('/login', ['username' => 'abello@example.test', 'password' => 'secret-pass']);

        $this->assertGuest();
    }

    public function test_users_can_not_sign_in_with_a_wrong_password(): void
    {
        $this->klaesUser();

        $this->post('/login', ['username' => 'abello', 'password' => 'wrong-password']);

        $this->assertGuest();
    }

    public function test_inactive_klaes_accounts_are_refused_like_in_klaes(): void
    {
        $this->klaesUser(['username' => 'off', 'is_active' => 0]);
        $this->klaesUser(['username' => 'blank', 'is_active' => null]);

        foreach (['off', 'blank'] as $username) {
            $this->post('/login', ['username' => $username, 'password' => 'secret-pass'])
                ->assertSessionHasErrors(['username' => 'Your account is temporarily inactive. Please contact your administrator to reactivate your account.']);

            $this->assertGuest();
        }
    }

    public function test_unverified_klaes_accounts_are_refused_like_in_klaes(): void
    {
        $this->klaesUser(['email_verified_at' => null]);

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_older_2a_bcrypt_passwords_from_klaes_still_work(): void
    {
        $user = $this->klaesUser(['password' => crypt('secret-pass', '$2a$10$abcdefghijklmnopqrstuv')]);

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_signing_in_and_out_never_writes_the_klaes_account(): void
    {
        $this->klaesUser(['remember_token' => 'set-by-klaes']);
        $before = (array) DB::connection('klaes')->table('users')->first();

        $this->post('/login', ['username' => 'abello', 'password' => 'secret-pass', 'remember' => 'on']);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
        $this->assertSame($before, (array) DB::connection('klaes')->table('users')->first());
    }
}
