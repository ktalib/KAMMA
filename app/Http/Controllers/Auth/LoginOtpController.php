<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * The sign-in code screen (step 2 of 2), ported from KLAES Auth\LoginOtpController: type the code, ask for a new one,
 * switch between SMS and email, or cancel back to the username + password form.
 */
class LoginOtpController extends Controller
{
    public function __construct(private LoginOtpService $otp)
    {
    }

    public function show(Request $request)
    {
        $user = $this->otp->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'Your sign-in has expired. Enter your username and password again.');
        }

        $channels = $this->otp->channels($user);
        $channel = $this->otp->lastChannel($user) ?? ($channels[0] ?? LoginOtpService::CHANNEL_EMAIL);

        return view('auth.login-otp', [
            'channel' => $channel,
            'where' => $this->otp->where($user, $channel),
            'other' => collect($channels)->first(fn ($c) => $c !== $channel),
            'otherWhere' => ($o = collect($channels)->first(fn ($c) => $c !== $channel)) ? $this->otp->where($user, $o) : null,
            'cooldown' => $this->otp->cooldownRemaining($user, $channel),
            'ttlMinutes' => $this->otp->ttlMinutes(),
            'codeLength' => $this->otp->codeLength(),
        ]);
    }

    public function verify(Request $request)
    {
        $length = $this->otp->codeLength();
        $request->validate(['code' => ['required', 'digits:'.$length]], [
            'code.required' => 'Enter the sign-in code.',
            'code.digits' => "Enter the {$length}-digit sign-in code.",
        ]);

        $result = $this->otp->verify($request, (string) $request->input('code'));
        if (! $result['verified']) {
            return $this->otp->pendingUser($request)
                ? redirect()->route('login.otp')->with('error', $result['message'])
                : redirect()->route('login')->with('error', $result['message']);
        }

        Auth::guard('web')->login($result['user'], $result['remember']);
        $request->session()->regenerate();
        $this->otp->markPassed($request, $result['user']);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request)
    {
        $request->validate(['channel' => ['required', Rule::in([LoginOtpService::CHANNEL_SMS, LoginOtpService::CHANNEL_EMAIL])]]);
        if (! $this->otp->pendingUser($request)) {
            return redirect()->route('login')->with('error', 'Your sign-in has expired. Enter your username and password again.');
        }

        $result = $this->otp->send($request, (string) $request->input('channel'));

        return redirect()->route('login.otp')->with($result['sent'] ? 'success' : 'error', $result['message']);
    }

    public function cancel(Request $request)
    {
        $this->otp->clear($request);

        return redirect()->route('login');
    }
}
