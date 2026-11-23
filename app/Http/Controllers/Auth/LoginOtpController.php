<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

        return view('auth.login-otp', [
            'maskedPhone' => $this->otp->maskedPhone($user),
            'maskedEmail' => $this->otp->maskedEmail($user),
            'channel' => $this->otp->maskedEmail($user) ? 'email' : 'sms',
            'ttlMinutes' => $this->otp->ttlMinutes(),
            'retryAfter' => $this->otp->retryAfter(),
            'expiresIn' => $this->otp->ttlMinutes() * 60,
            'codeLength' => 6,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']], [
            'code.required' => 'Enter the sign-in code.',
            'code.size' => 'Enter the 6-digit sign-in code.',
        ]);

        $result = $this->otp->verify($request, (string) $request->input('code'));
        if (! $result['verified']) {
            if (! $this->otp->pendingUser($request)) {
                return redirect()->route('login')->with('error', $result['message']);
            }
            return redirect()->route('login.otp')->with('error', $result['message']);
        }

        Auth::guard('web')->login($result['user'], $result['remember']);
        $request->session()->regenerate();
        $this->otp->markPassed($request, $result['user']);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request)
    {
        $request->validate([
            'channel' => ['required', Rule::in(['email', 'sms'])],
        ]);
        $user = $this->otp->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'Your sign-in has expired.');
        }

        $pending = $this->otp->pending($request);
        $result = $this->otp->begin($request, $user, (bool) ($pending['remember'] ?? false));
        return redirect()->route('login.otp')->with($result['sent'] ? 'success' : 'error', $result['message']);
    }

    public function cancel(Request $request)
    {
        $this->otp->clear($request);
        return redirect()->route('login');
    }
}
