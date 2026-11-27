<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\PortalApplicant;
use App\Services\BulkSmsNgService;
use App\Support\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Applicant portal sign-in, register and phone check. Laid out like the KLAES LAAS portal (form + assurance panel).
 *
 * Accounts are kamma_portal_applicants (since 2026-11-25). Register checks the details and sends a 6-digit code to the
 * mobile; the account is created only when the code is confirmed. The code goes by SMS (sender KLAES-METRO) when
 * config kamma.portal.sms_codes is on (production), otherwise it is logged and shown on the code screen (dev, user
 * 2026-11-25: "Log on dev, SMS on live"). The demo applicant skips the code, like the KLAES MLPP-SG demo surveyor.
 * Nothing is written to KLAES dbo tables.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login', ['demo' => $this->demoDetails()]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ], [], ['login' => 'username, phone or email']);

        $demo = (array) config('kamma.portal.demo');
        $login = mb_strtolower(trim($data['login']));
        $isDemo = config('kamma.portal.demo_enabled')
            && in_array($login, array_filter([mb_strtolower((string) ($demo['username'] ?? '')), (string) ($demo['phone'] ?? '')]), true)
            && hash_equals((string) ($demo['password'] ?? ''), $data['password']);

        if ($isDemo) {
            PortalSession::signInDemo($request);

            return redirect()->intended(route('portal.dashboard'));
        }

        $account = PortalApplicant::findForLogin($data['login']);
        if ($account === null || ! Hash::check($data['password'], $account->password)) {
            throw ValidationException::withMessages(['login' => 'Those details do not match an account.']);
        }
        if (! $account->is_active) {
            throw ValidationException::withMessages(['login' => 'This account has been switched off. Contact KAMMA.']);
        }

        PortalSession::signInAccount($request, $account);

        return redirect()->intended(route('portal.dashboard'));
    }

    /** One-click sign-in as the demo applicant (not on production). */
    public function demo(Request $request): RedirectResponse
    {
        abort_unless(config('kamma.portal.demo_enabled'), 404);
        PortalSession::signInDemo($request);

        return redirect()->route('portal.dashboard')->with('status', 'Signed in as the demo applicant. Nothing you do here is saved.');
    }

    public function showRegister(): View
    {
        return view('portal.auth.register');
    }

    public function register(Request $request, BulkSmsNgService $sms): RedirectResponse
    {
        $request->merge([
            // Names: single spaces, capital first letters. Email lower case. Phone kept as typed (KLAES rule).
            'name' => is_string($request->input('name')) ? Str::title(trim(preg_replace('/\s+/u', ' ', $request->input('name')))) : $request->input('name'),
            'email' => is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : $request->input('email'),
            'phone' => is_string($request->input('phone')) ? trim($request->input('phone')) : $request->input('phone'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^(\+?234|0)[789][01]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'phone.regex' => 'Enter a Nigerian mobile number, e.g. 08031234567.',
            'terms.accepted' => 'Tick the box to confirm the details are yours.',
        ], ['phone' => 'mobile number']);

        $this->ensureFree($data['phone'], $data['email'] ?? null);

        $code = (string) random_int(100000, 999999);
        $request->session()->put(PortalSession::PENDING, [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'code_hash' => Hash::make($code),
            // Shown on the code screen only when no SMS is sent (dev).
            'code' => $this->smsCodes() ? null : $code,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10)->toDateTimeString(),
        ]);

        $this->sendCode($sms, $data['phone'], $code);

        return redirect()->route('portal.verify');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(PortalSession::PENDING);
        if (! is_array($pending)) {
            return redirect()->route('portal.register');
        }

        return view('portal.auth.verify', ['pending' => $pending, 'smsSent' => $this->smsCodes()]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(PortalSession::PENDING);
        if (! is_array($pending)) {
            return redirect()->route('portal.register');
        }

        $data = $request->validate(['code' => ['required', 'digits:6']], [], ['code' => 'code']);

        if (now()->greaterThan($pending['expires_at']) || ($pending['attempts'] ?? 0) >= 5) {
            $request->session()->forget(PortalSession::PENDING);

            return redirect()->route('portal.register')->withErrors(['phone' => 'The code has expired. Register again to get a new code.']);
        }
        if (! Hash::check($data['code'], $pending['code_hash'])) {
            $pending['attempts'] = ($pending['attempts'] ?? 0) + 1;
            $request->session()->put(PortalSession::PENDING, $pending);
            throw ValidationException::withMessages(['code' => 'That code is wrong. Check it and try again.']);
        }

        // Taken while the code was on its way?
        $this->ensureFree($pending['phone'], $pending['email']);

        $account = PortalApplicant::create([
            'username' => PortalApplicant::localPhone($pending['phone']),
            'name' => $pending['name'],
            'phone' => $pending['phone'],
            'email' => $pending['email'],
            'password' => $pending['password'], // already hashed; the 'hashed' cast keeps it as is
            'verified_at' => now(),
            'is_active' => true,
        ]);

        $request->session()->forget(PortalSession::PENDING);
        PortalSession::signInAccount($request, $account);

        return redirect()->route('portal.dashboard')->with('status', 'Your account is ready. Sign in next time with your mobile number and password.');
    }

    public function logout(Request $request): RedirectResponse
    {
        PortalSession::signOut($request);

        return redirect()->route('portal.login')->with('status', 'You have signed out.');
    }

    /** One account per mobile number and per email. */
    private function ensureFree(string $phone, ?string $email): void
    {
        $errors = [];
        if (PortalApplicant::query()->where('username', PortalApplicant::localPhone($phone))->exists()) {
            $errors['phone'] = 'An account already uses this mobile number. Sign in instead.';
        }
        if ($email && PortalApplicant::query()->where('email', $email)->exists()) {
            $errors['email'] = 'An account already uses this email. Sign in instead.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function smsCodes(): bool
    {
        return (bool) config('kamma.portal.sms_codes');
    }

    private function sendCode(BulkSmsNgService $sms, string $phone, string $code): void
    {
        $number = BulkSmsNgService::normalizeNumber($phone);
        if (! $this->smsCodes() || $number === null || in_array($number, (array) config('phone_verification.placeholder_phones', []), true)) {
            Log::info('KAMMA portal: registration code not texted (sms_codes off or placeholder number)', ['phone' => $phone, 'code' => $code]);

            return;
        }

        $accepted = $sms->sendFirstAccepted($number, [
            "Your KLAES-METRO portal code is {$code}. It expires in 10 minutes. Do not share it.",
            "KLAES-METRO code: {$code}",
        ], config('kamma.portal.sms_sender'));
        if ($accepted === null) {
            Log::warning('KAMMA portal: registration code not delivered by SMS', ['phone' => $phone, 'reason' => $sms->lastFailureReason()]);
        }
    }

    /** @return array{username: string, password: string}|null */
    private function demoDetails(): ?array
    {
        if (! config('kamma.portal.demo_enabled')) {
            return null;
        }
        $demo = (array) config('kamma.portal.demo');

        return ['username' => (string) $demo['username'], 'password' => (string) $demo['password']];
    }
}
