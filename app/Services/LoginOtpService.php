<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LoginOtpService
{
    public const SESSION_KEY = 'login_otp.pending';
    public const PASSED_KEY = 'login_otp.passed_user';

    public function requiredFor(User $user): bool
    {
        return (bool) ($user->is_otp_verified ?? false)
            && trim((string) ($user->phone_number ?? '')) !== '';
    }

    public function begin(Request $request, User $user, bool $remember): array
    {
        $request->session()->regenerate();
        $request->session()->forget(self::PASSED_KEY);
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'remember' => $remember,
            'started_at' => now()->timestamp,
        ]);

        $length = max(4, min(10, (int) config('phone_verification.code_length', 6)));
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $ttl = max(1, (int) config('phone_verification.ttl_minutes', 10));

        Cache::put('login_otp.code.'.$user->id, [
            'hash' => hash('sha256', $code),
            'sent_at' => now()->timestamp,
            'channel' => $this->emailFor($user) ? 'email' : 'sms',
            'attempts' => 0,
        ], now()->addMinutes($ttl));

        $email = $this->emailFor($user);
        if ($email) {
            try {
                Mail::raw("Your KLAES-METRO sign-in code is {$code}. It expires in {$ttl} minutes.", function ($message) use ($email) {
                    $message->to($email)->subject('Your KLAES-METRO sign-in code');
                });
                return ['sent' => true, 'message' => 'A sign-in code was sent to your email.'];
            } catch (\Throwable $e) {
                Log::warning('KAMMA OTP email send failed', ['error' => $e->getMessage()]);
            }
        }

        $sms = $this->sendSms((string) $user->phone_number, "Your KLAES-METRO sign-in code is {$code}. It expires in {$ttl} minutes.");
        if ($sms) {
            return ['sent' => true, 'message' => 'A sign-in code was sent to your phone.'];
        }

        return ['sent' => false, 'message' => 'A code could not be sent. Check the mail/SMS configuration and try again.'];
    }

    public function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending) || empty($pending['user_id'])) {
            return null;
        }

        return User::query()->find($pending['user_id']);
    }

    public function pending(Request $request): array
    {
        return (array) $request->session()->get(self::SESSION_KEY, []);
    }

    public function verify(Request $request, string $code): array
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return ['verified' => false, 'message' => 'Your sign-in has expired. Enter your username and password again.', 'user' => null, 'remember' => false];
        }
        $pending = $this->pending($request);

        $live = Cache::get('login_otp.code.'.$user->id);
        if (! is_array($live) || hash('sha256', trim($code)) !== ($live['hash'] ?? null)) {
            $attempts = (int) ($live['attempts'] ?? 0) + 1;
            if (is_array($live)) {
                $live['attempts'] = $attempts;
                Cache::put('login_otp.code.'.$user->id, $live, now()->addMinutes(max(1, (int) config('phone_verification.ttl_minutes', 10))));
            }
            if ($attempts >= 5) {
                Cache::forget('login_otp.code.'.$user->id);
                $this->clear($request);
                return ['verified' => false, 'message' => 'Too many incorrect attempts. Sign in again.', 'user' => null, 'remember' => false];
            }
            return ['verified' => false, 'message' => 'The code is incorrect. Try again.', 'user' => null, 'remember' => false];
        }

        Cache::forget('login_otp.code.'.$user->id);
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->put(self::PASSED_KEY, $user->id);

        return [
            'verified' => true,
            'message' => 'Verified',
            'user' => $user,
            'remember' => (bool) ($pending['remember'] ?? false),
        ];
    }

    public function markPassed(Request $request, User $user): void
    {
        $request->session()->put(self::PASSED_KEY, $user->id);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget([self::SESSION_KEY, self::PASSED_KEY]);
    }

    public function ttlMinutes(): int
    {
        return max(1, (int) config('phone_verification.ttl_minutes', 10));
    }

    public function retryAfter(): int
    {
        return max(0, (int) config('phone_verification.resend_cooldown_seconds', 60));
    }

    public function maskedPhone(User $user): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) ($user->phone_number ?? ''));
        if (strlen($phone) < 7) {
            return null;
        }
        return substr($phone, 0, 3).'***'.substr($phone, -3);
    }

    public function maskedEmail(User $user): ?string
    {
        $email = trim((string) ($user->email ?? ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        [$name, $domain] = explode('@', $email, 2);
        $shown = strlen($name) <= 2 ? substr($name, 0, 1).'*' : substr($name, 0, 2).'***';
        return $shown.'@'.$domain;
    }

    private function emailFor(User $user): ?string
    {
        $email = trim((string) ($user->email ?? ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || str_contains(strtolower($email), 'example')) {
            return null;
        }
        return $email;
    }

    private function sendSms(string $phone, string $message): bool
    {
        $email = config('services.bulk_sms_ng.email');
        $password = config('services.bulk_sms_ng.password');
        $sender = config('services.bulk_sms_ng.sender', 'KAMMA');
        if (! $email || ! $password) {
            return false;
        }
        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '234'.substr($phone, 1);
        }
        $ch = curl_init('https://account.bulk-sms.ng/api/promotional/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POSTFIELDS => http_build_query([
                'email' => $email,
                'password' => $password,
                'senderid' => $sender,
                'mobile' => $phone,
                'message' => $message,
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response !== false;
    }
}
