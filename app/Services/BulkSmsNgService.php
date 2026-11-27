<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Bulk-SMS.ng gateway (account.bulk-sms.ng), copied from KLAES App\Services\BulkSmsNgService with the same account
 * (BULK_SMS_NG_* in .env, same logins as KLAES).
 *
 * What KLAES learned about this gateway and this copy keeps:
 *  - only the PROMOTIONAL route is enabled on the account (transactional answers 608);
 *  - promotional SMS sent between 19:45 and 08:00 is held by the networks until 08:30 (code 609, accepted);
 *  - a successful send answers HTTP 200 with an EMPTY body; failures answer JSON with a statusCode;
 *  - a sender ID that is not registered with the vendor is accepted, billed and delivered to nobody, and the
 *    gateway cuts sender IDs at 11 characters. Confirm a new sender with Bulk-SMS.ng before using it.
 *
 * No SMS goes out from the console (tests, tinker, artisan) unless BULK_SMS_NG_ALLOW_CONSOLE=true: KLAES learned that
 * Http::fake() does not stop this curl call.
 */
class BulkSmsNgService
{
    private string $base = 'https://account.bulk-sms.ng/api';

    protected ?string $lastCode = null;

    protected ?string $lastReason = null;

    private const STATUS_TEXT = [
        '600' => 'success',
        '601' => 'authentication failed: check BULK_SMS_NG_EMAIL / BULK_SMS_NG_PASSWORD',
        '602' => 'invalid request (a required field is missing or malformed)',
        '604' => 'insufficient balance on the Bulk-SMS.ng account',
        '606' => 'gateway internal error, try again later',
        '607' => 'more than 100 recipients in one request',
        '608' => 'this account is not authorised for that route',
        '609' => 'accepted but deferred: the networks do not deliver promotional SMS overnight',
    ];

    /** The only status a second wording is tried on; the others are account problems rewording cannot fix. */
    public const CODE_REWORDABLE = '602';

    public function lastFailureReason(): ?string
    {
        return $this->lastReason;
    }

    public function lastStatusCode(): ?string
    {
        return $this->lastCode;
    }

    /**
     * Send the first wording the gateway accepts.
     *
     * @param  array<int,string|null>  $messages  Best wording first.
     * @return string|null The message that was accepted, or null if none was.
     */
    public function sendFirstAccepted(string $phone, array $messages, ?string $sender = null): ?string
    {
        foreach (array_values(array_filter($messages)) as $message) {
            if ($this->send($phone, $message, $sender)) {
                return $message;
            }
            if ($this->lastCode !== self::CODE_REWORDABLE) {
                return null;
            }
        }

        return null;
    }

    public function send(string $phone, string $message, ?string $sender = null): bool
    {
        $this->lastCode = null;
        $this->lastReason = null;

        if (app()->runningInConsole() && ! config('services.bulk_sms_ng.allow_console')) {
            $this->lastReason = 'SMS is not sent from the console (tests, tinker, artisan)';
            Log::info('BulkSmsNgService: send refused outside the web app', ['phone' => $phone]);

            return false;
        }

        $email = config('services.bulk_sms_ng.email');
        $password = config('services.bulk_sms_ng.password');
        $sender = $sender ?: config('services.bulk_sms_ng.sender', 'KLAES-CORE');

        if (! $email || ! $password) {
            $this->lastReason = 'BULK_SMS_NG_EMAIL / BULK_SMS_NG_PASSWORD are not set in this environment';
            Log::warning('BulkSmsNgService: credentials not configured.');

            return false;
        }

        $mobile = self::normalizeNumber($phone);
        if (! $mobile) {
            $this->lastReason = 'the phone number is not a usable Nigerian mobile number';

            return false;
        }

        [$body, $http, $error] = $this->post('/promotional/send', [
            'email' => $email,
            'password' => $password,
            'message' => $message,
            'recipient' => $mobile,
            'senderid' => substr($sender, 0, 11),
            'smsgateway' => (string) config('services.bulk_sms_ng.gateway', '1'),
        ]);

        if ($error) {
            $this->lastReason = 'could not reach account.bulk-sms.ng ('.$error.')';
            Log::error('BulkSmsNgService: request failed', ['phone' => $mobile, 'error' => $error]);

            return false;
        }

        $ok = $this->interpret($body, $http);

        Log::log($ok ? 'info' : 'warning', 'BulkSmsNgService: send result', [
            'phone' => $mobile,
            'sender' => $sender,
            'http_code' => $http,
            'response' => $body === '' ? '<empty>' : mb_substr($body, 0, 1000),
            'code' => $this->lastCode,
            'reason' => $this->lastReason,
            'success' => $ok,
        ]);

        return $ok;
    }

    /** Account balance in naira, or null if it could not be read. */
    public function balance(): ?string
    {
        [$body, , $error] = $this->post('/promotional/balance', [
            'email' => config('services.bulk_sms_ng.email'),
            'password' => config('services.bulk_sms_ng.password'),
        ]);

        return $error ? null : (json_decode($body, true)['balance'] ?? null);
    }

    private function interpret(string $body, int $http): bool
    {
        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            $this->lastCode = isset($decoded['statusCode']) ? (string) $decoded['statusCode'] : null;
            if (strtolower((string) ($decoded['status'] ?? '')) === 'success') {
                return true;
            }
            $this->lastReason = self::STATUS_TEXT[$this->lastCode] ?? ($decoded['message'] ?? 'unrecognised gateway response');

            return false;
        }

        if ($http >= 200 && $http < 300 && trim($body) === '') {
            return true;
        }

        // KLAES verified (handset + wallet) that the gateway delivers while answering an empty HTTP 500.
        if ($http === 500 && trim($body) === '') {
            $this->lastCode = '500-accepted';

            return true;
        }

        $this->lastReason = $http >= 200 && $http < 300
            ? 'gateway replied with something other than its documented response'
            : 'gateway returned HTTP '.$http;

        return false;
    }

    /** @return array{0:string, 1:int, 2:string} body, HTTP status, curl error */
    private function post(string $path, array $payload): array
    {
        $ch = curl_init($this->base.$path);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [trim((string) $body), $http, $error];
    }

    /** 234XXXXXXXXXX, or null when the number is not a usable Nigerian mobile. */
    public static function normalizeNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '234') && strlen($digits) === 13) {
            return $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '234'.substr($digits, 1);
        }
        if (strlen($digits) === 10) {
            return '234'.$digits;
        }

        return null;
    }
}
