<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Applicant portal sign-in, register and phone check. Laid out like the KLAES LAAS portal (form + assurance panel).
 *
 * UI first (user 2026-11-23): accounts are not stored yet. Only the demo applicant can sign in with a password; it
 * skips the phone code, like the KLAES MLPP-SG demo surveyor. Register checks the details, shows the phone-code step
 * with the code on screen (no SMS is sent) and signs the new applicant in for this browser session only.
 * Nothing is written to KLAES dbo tables or any KAMMA table.
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

        if (! $isDemo) {
            throw ValidationException::withMessages([
                'login' => config('kamma.portal.demo_enabled')
                    ? 'Those details do not match an account. While the portal is in preview only the demo applicant can sign in.'
                    : 'Those details do not match an account.',
            ]);
        }

        PortalSession::signIn($request, PortalSession::demoAccount(), true);

        return redirect()->intended(route('portal.dashboard'));
    }

    /** One-click sign-in as the demo applicant (preview only). */
    public function demo(Request $request): RedirectResponse
    {
        abort_unless(config('kamma.portal.demo_enabled'), 404);
        PortalSession::signIn($request, PortalSession::demoAccount(), true);

        return redirect()->route('portal.dashboard')->with('status', 'Signed in as the demo applicant. Nothing you do here is saved.');
    }

    public function showRegister(): View
    {
        return view('portal.auth.register');
    }

    public function register(Request $request): RedirectResponse
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

        $request->session()->put(PortalSession::PENDING, [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            // Preview: the code is shown on the next screen because no SMS is sent yet.
            'code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinutes(10)->toDateTimeString(),
        ]);

        return redirect()->route('portal.verify');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get(PortalSession::PENDING);
        if (! is_array($pending)) {
            return redirect()->route('portal.register');
        }

        return view('portal.auth.verify', ['pending' => $pending]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(PortalSession::PENDING);
        if (! is_array($pending)) {
            return redirect()->route('portal.register');
        }

        $data = $request->validate(['code' => ['required', 'digits:6']], [], ['code' => 'code']);

        if (now()->greaterThan($pending['expires_at']) || ! hash_equals($pending['code'], $data['code'])) {
            throw ValidationException::withMessages(['code' => 'That code is wrong or has expired. Check it and try again.']);
        }

        $request->session()->forget(PortalSession::PENDING);
        PortalSession::signIn($request, [
            'username' => preg_replace('/\D+/', '', $pending['phone']),
            'name' => $pending['name'],
            'phone' => $pending['phone'],
            'email' => $pending['email'],
        ], false);

        return redirect()->route('portal.dashboard')
            ->with('status', 'Your account is ready (preview: it lasts until you sign out and is not saved yet).');
    }

    public function logout(Request $request): RedirectResponse
    {
        PortalSession::signOut($request);

        return redirect()->route('portal.login')->with('status', 'You have signed out.');
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
