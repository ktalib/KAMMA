<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The second step of signing in, ported from KLAES App\Services\LoginOtpService:
 *
 *   1. username + password are checked WITHOUT signing in;
 *   2. a code goes to the account's confirmed mobile (SMS) or its real email address;
 *   3. the code is typed back and only then is the session signed in.
 *
 * Between 1 and 3 the browser is a guest holding a pending sign-in in its session. The code lives per ACCOUNT in the
 * cache as an HMAC (never the code itself) with when and how it was sent and the wrong tries, so a second tab or a
 * repeat sign-in within a few minutes reuses it instead of texting again. Nothing is written to dbo.users (read-only).
 * Policy and wording: config/phone_verification.php 'login_otp'.
 */
class LoginOtpService
{
    public const SESSION_KEY = 'login_otp.pending';

    public const PASSED_KEY = 'login_otp.passed_user';

    public const CODE_CACHE = 'login_otp.code.';

    public const SMS_LOG_CACHE = 'login_otp.sms.';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_EMAIL = 'email';

    public function __construct(private BulkSmsNgService $gateway)
    {
    }

    /* ── policy ───────────────────────────────────────────────────────────── */

    public function requiredFor(User $user): bool
    {
        if (! $this->config('enabled', true) || $this->isExempt($user)) {
            return false;
        }

        return $this->channels($user) !== [];
    }

    public function isExempt(User $user): bool
    {
        $usernames = array_map('strtolower', (array) $this->config('exempt_usernames', []));
        $emails = array_map('strtolower', (array) $this->config('exempt_emails', []));

        return in_array(strtolower(trim((string) $user->username)), $usernames, true)
            || in_array(strtolower(trim((string) $user->email)), $emails, true);
    }

    /**
     * The routes this account can be sent a code by, best first.
     *
     * SMS only to a mobile KLAES has confirmed by a code (is_otp_verified with otp_channel sms/both, as in KLAES): a
     * number nobody has proved never receives a sign-in code. Email to any real (non-placeholder) address.
     *
     * @return list<string>
     */
    public function channels(User $user): array
    {
        $channels = [];
        if ($this->phoneFor($user) !== null) {
            $channels[] = self::CHANNEL_SMS;
        }
        if ($this->emailFor($user) !== null && ($channels !== [] || $this->config('email_when_no_phone', true))) {
            $channels[] = self::CHANNEL_EMAIL;
        }

        return $channels;
    }

    public function phoneFor(User $user): ?string
    {
        if (! (bool) ($user->getAttribute('is_otp_verified') ?? false)) {
            return null;
        }
        $recorded = strtolower(trim((string) ($user->getAttribute('otp_channel') ?? '')));
        if ($recorded !== '' && ! in_array($recorded, ['sms', 'both'], true)) {
            return null;
        }
        $phone = BulkSmsNgService::normalizeNumber((string) ($user->phone_number ?? ''));

        return ($phone === null || in_array($phone, (array) config('phone_verification.placeholder_phones', []), true)) ? null : $phone;
    }

    public function emailFor(User $user): ?string
    {
        $email = mb_strtolower(trim((string) ($user->email ?? '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $domain = substr(strrchr($email, '@') ?: '', 1);
        foreach ((array) config('phone_verification.placeholder_email_domains', []) as $pattern) {
            $pattern = mb_strtolower(trim((string) $pattern));
            if ($pattern !== '' && ($domain === ltrim($pattern, '.') || (str_starts_with($pattern, '.') && str_ends_with($domain, $pattern)))) {
                return null;
            }
        }

        return $email;
    }

    public function firstChannel(User $user): string
    {
        $channels = $this->channels($user);
        if (in_array(self::CHANNEL_EMAIL, $channels, true)
            && ($channels[0] === self::CHANNEL_EMAIL || ($this->config('quiet_hours_email_first', true) && $this->inQuietHours()))) {
            return self::CHANNEL_EMAIL;
        }

        return $channels[0] ?? self::CHANNEL_EMAIL;
    }

    /** 19:45–08:00 Kano time: the networks hold promotional SMS until about 08:30. */
    public function inQuietHours(): bool
    {
        $hhmm = (int) now(config('phone_verification.timezone', 'Africa/Lagos'))->format('Hi');

        return $hhmm >= 1945 || $hhmm < 800;
    }

    public function ttlMinutes(): int
    {
        return max(1, (int) $this->config('ttl_minutes', 10));
    }

    public function cooldownSeconds(): int
    {
        return max(0, (int) $this->config('resend_cooldown_seconds', 60));
    }

    public function codeLength(): int
    {
        return max(4, min(8, (int) $this->config('code_length', 6)));
    }

    /* ── the pending sign-in ──────────────────────────────────────────────── */

    /** @return array{sent:bool, message:string} */
    public function begin(Request $request, User $user, bool $remember): array
    {
        // A fresh session id for the pending state: whoever held the old one does not inherit the half-finished sign-in.
        $request->session()->regenerate();
        $request->session()->forget(self::PASSED_KEY);
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'remember' => $remember,
            'started_at' => now()->timestamp,
        ]);

        $live = $this->liveCode($user);
        if ($live && now()->timestamp - (int) $live['sent_at'] < max(0, (int) $this->config('reuse_minutes', 5)) * 60) {
            return ['sent' => true, 'message' => 'A code was sent to '.$this->where($user, $live['channel']).' a moment ago. Enter that code, or ask for a new one below.'];
        }

        return $this->send($request, $this->firstChannel($user));
    }

    public function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending) || empty($pending['user_id'])) {
            return null;
        }
        if (now()->timestamp - (int) ($pending['started_at'] ?? 0) > max(1, (int) $this->config('challenge_minutes', 30)) * 60) {
            $this->clear($request);

            return null;
        }

        $user = User::query()->find($pending['user_id']);
        if (! $user || (string) $user->is_active === '0') {
            $this->clear($request);

            return null;
        }

        return $user;
    }

    public function pending(Request $request): array
    {
        return (array) $request->session()->get(self::SESSION_KEY, []);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /** @return array{hash:?string, sent_at:int, channel:string, attempts:int}|null */
    public function liveCode(User $user): ?array
    {
        $code = Cache::get(self::CODE_CACHE.$user->id);

        return (is_array($code) && ! empty($code['hash']) && now()->timestamp - (int) $code['sent_at'] <= $this->ttlMinutes() * 60) ? $code : null;
    }

    /** The route the last code went by (even a spent one), for the code screen. */
    public function lastChannel(User $user): ?string
    {
        $code = Cache::get(self::CODE_CACHE.$user->id);

        return is_array($code) ? ($code['channel'] ?? null) : null;
    }

    /** Seconds before another code may be sent by $channel. Switching route is always allowed at once. */
    public function cooldownRemaining(User $user, string $channel): int
    {
        $last = Cache::get(self::CODE_CACHE.$user->id);
        if (! is_array($last) || empty($last['sent_at']) || ($last['channel'] ?? null) !== $channel) {
            return 0;
        }

        return max(0, $this->cooldownSeconds() - (now()->timestamp - (int) $last['sent_at']));
    }

    /* ── sending ──────────────────────────────────────────────────────────── */

    /** @return array{sent:bool, message:string} */
    public function send(Request $request, string $channel): array
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return ['sent' => false, 'message' => 'Your sign-in has expired. Enter your username and password again.'];
        }
        if (! in_array($channel, $this->channels($user), true)) {
            return ['sent' => false, 'message' => $channel === self::CHANNEL_SMS
                ? 'Your account has no confirmed mobile number. Use your email address, or confirm your number in KLAES.'
                : 'Your account has no usable email address. Use your phone, or contact ICT.'];
        }

        $wait = $this->cooldownRemaining($user, $channel);
        if ($wait > 0) {
            return ['sent' => false, 'message' => "Wait {$wait} seconds before asking for another code."];
        }

        if ($channel === self::CHANNEL_SMS && $this->smsSentThisHour($user) >= max(1, (int) $this->config('max_sms_per_hour', 6))) {
            return ['sent' => false, 'message' => $this->emailFor($user)
                ? 'Too many codes have been texted to this account in the last hour. Use "Email it to me instead", or try again later.'
                : 'Too many codes have been texted to this account in the last hour. Try again later, or contact ICT.'];
        }

        $code = str_pad((string) random_int(0, (10 ** $this->codeLength()) - 1), $this->codeLength(), '0', STR_PAD_LEFT);

        // Stored before the send and kept if it fails: the gateway may have accepted it whatever it answered, and the
        // cooldown must apply either way.
        Cache::put(self::CODE_CACHE.$user->id, [
            'hash' => $this->hash($code, $user),
            'sent_at' => now()->timestamp,
            'channel' => $channel,
            'attempts' => 0,
        ], now()->addMinutes($this->ttlMinutes())->addSeconds($this->cooldownSeconds()));

        return $channel === self::CHANNEL_EMAIL ? $this->sendByEmail($user, $code) : $this->sendBySms($user, $code);
    }

    private function sendBySms(User $user, string $code): array
    {
        $this->recordSms($user);
        $tokens = ['{code}' => $code, '{minutes}' => (string) $this->ttlMinutes()];
        $accepted = $this->gateway->sendFirstAccepted((string) $this->phoneFor($user), [
            strtr((string) $this->config('message'), $tokens),
            strtr((string) $this->config('message_fallback'), $tokens),
        ]);

        if ($accepted === null) {
            Log::warning('LoginOtpService: sign-in code not delivered by SMS', [
                'user_id' => $user->id, 'gateway_code' => $this->gateway->lastStatusCode(), 'reason' => $this->gateway->lastFailureReason(),
            ]);

            return ['sent' => false, 'message' => 'The code could not be texted ('.($this->gateway->lastFailureReason() ?? 'gateway error').').'
                .($this->emailFor($user) ? ' Use "Email it to me instead".' : ' Try again, or contact ICT.')];
        }

        $late = $this->gateway->lastStatusCode() === '609' || $this->inQuietHours();

        return ['sent' => true, 'message' => 'A sign-in code has been sent to '.$this->maskPhone($this->phoneFor($user)).'. It expires in '.$this->ttlMinutes().' minutes.'
            .($late ? ' At this hour the networks may hold it until about 08:30: use your email if it does not arrive.' : '')];
    }

    private function sendByEmail(User $user, string $code): array
    {
        $email = (string) $this->emailFor($user);
        $brand = (string) config('app.brand', 'KLAES-METRO');

        try {
            Mail::raw(
                "Your {$brand} sign-in code is {$code}.\n\nIt expires in {$this->ttlMinutes()} minutes. Do not share it with anyone. "
                ."If you did not try to sign in, change your KLAES password and tell ICT.",
                fn ($message) => $message->to($email)->subject("Your {$brand} sign-in code")
            );
        } catch (\Throwable $e) {
            Log::warning('LoginOtpService: sign-in code email failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return ['sent' => false, 'message' => 'The code could not be emailed.'.($this->phoneFor($user) ? ' Use "Text it to me instead".' : ' Try again, or contact ICT.')];
        }

        return ['sent' => true, 'message' => 'A sign-in code has been sent to '.$this->maskEmail($email).'. It expires in '.$this->ttlMinutes().' minutes. Check your spam folder if it is not in your inbox.'];
    }

    private function smsSentThisHour(User $user): int
    {
        return count(array_filter((array) Cache::get(self::SMS_LOG_CACHE.$user->id, []), fn ($t) => $t > now()->timestamp - 3600));
    }

    private function recordSms(User $user): void
    {
        $stamps = array_filter((array) Cache::get(self::SMS_LOG_CACHE.$user->id, []), fn ($t) => $t > now()->timestamp - 3600);
        $stamps[] = now()->timestamp;
        Cache::put(self::SMS_LOG_CACHE.$user->id, array_values($stamps), now()->addHour());
    }

    /* ── verifying ────────────────────────────────────────────────────────── */

    /** @return array{verified:bool, message:string, user:User|null, remember:bool} */
    public function verify(Request $request, string $code): array
    {
        $fail = fn (string $message) => ['verified' => false, 'message' => $message, 'user' => null, 'remember' => false];

        $user = $this->pendingUser($request);
        if (! $user) {
            return $fail('Your sign-in has expired. Enter your username and password again.');
        }

        $live = $this->liveCode($user);
        if (! $live) {
            return $fail('That code has expired. Ask for a new one below.');
        }

        if (! hash_equals((string) $live['hash'], $this->hash(trim($code), $user))) {
            $live['attempts'] = (int) ($live['attempts'] ?? 0) + 1;
            if ($live['attempts'] >= (int) $this->config('max_attempts', 5)) {
                // Spent, but kept so the cooldown still counts from the last send.
                $live['hash'] = null;
                Cache::put(self::CODE_CACHE.$user->id, $live, now()->addMinutes($this->ttlMinutes())->addSeconds($this->cooldownSeconds()));
                $this->clear($request);

                return $fail('Too many incorrect codes. Sign in again.');
            }
            Cache::put(self::CODE_CACHE.$user->id, $live, now()->addMinutes($this->ttlMinutes())->addSeconds($this->cooldownSeconds()));
            $left = (int) $this->config('max_attempts', 5) - $live['attempts'];

            return $fail("The code is incorrect. {$left} ".($left === 1 ? 'try' : 'tries').' left.');
        }

        Cache::forget(self::CODE_CACHE.$user->id);
        $remember = (bool) ($this->pending($request)['remember'] ?? false);
        $this->clear($request);

        return ['verified' => true, 'message' => 'Verified', 'user' => $user, 'remember' => $remember];
    }

    public function markPassed(Request $request, User $user): void
    {
        $request->session()->put(self::PASSED_KEY, $user->id);
    }

    /* ── display ──────────────────────────────────────────────────────────── */

    public function where(User $user, ?string $channel): string
    {
        return $channel === self::CHANNEL_SMS ? $this->maskPhone($this->phoneFor($user)) : $this->maskEmail($this->emailFor($user));
    }

    public function maskPhone(?string $phone): string
    {
        if (! $phone) {
            return 'your phone';
        }
        $local = str_starts_with($phone, '234') ? '0'.substr($phone, 3) : $phone;

        return substr($local, 0, 4).'****'.substr($local, -3);
    }

    public function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return 'your email';
        }
        [$name, $domain] = explode('@', $email, 2);

        return (mb_strlen($name) <= 2 ? mb_substr($name, 0, 1).'*' : mb_substr($name, 0, 2).'***').'@'.$domain;
    }

    private function hash(string $code, User $user): string
    {
        return hash_hmac('sha256', $user->id.'|'.$code, (string) config('app.key'));
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return config('phone_verification.login_otp.'.$key, $default);
    }
}
