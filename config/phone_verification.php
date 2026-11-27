<?php

/*
| Sign-in code (second step after username + password), ported from KLAES config/phone_verification.php 'login_otp'.
| Accounts are KLAES dbo.users, so who has a confirmed mobile (is_otp_verified + otp_channel) is decided in KLAES.
|
|   - a confirmed mobile (otp_channel sms/both)  -> code by SMS (email first between 19:45 and 08:00, when the
|     networks hold promotional SMS until 08:30);
|   - otherwise a real email address              -> code by email;
|   - neither                                     -> password only, as in KLAES.
| The user can switch route on the code screen. Gateway and mail logins are the same as KLAES (.env).
*/

return [
    'timezone' => env('PHONE_VERIFICATION_TIMEZONE', 'Africa/Lagos'),

    'login_otp' => [
        'enabled' => env('LOGIN_OTP_ENABLED', true),
        // Email-only accounts (a real address, no confirmed mobile) are asked for a code too.
        'email_when_no_phone' => env('LOGIN_OTP_EMAIL_WHEN_NO_PHONE', true),
        'code_length' => 6,
        'ttl_minutes' => (int) env('LOGIN_OTP_TTL_MINUTES', 10),
        // A second sign-in within this many minutes reuses the code already sent instead of texting again.
        'reuse_minutes' => (int) env('LOGIN_OTP_REUSE_MINUTES', 5),
        'resend_cooldown_seconds' => (int) env('LOGIN_OTP_RESEND_SECONDS', 60),
        'max_sms_per_hour' => (int) env('LOGIN_OTP_MAX_SMS_PER_HOUR', 6),
        'max_attempts' => 5,
        // How long the code screen stays open before username + password must be typed again.
        'challenge_minutes' => 30,
        'quiet_hours_email_first' => env('LOGIN_OTP_QUIET_HOURS_EMAIL_FIRST', true),
        'message' => env('LOGIN_OTP_MESSAGE', 'KLAES-METRO sign-in: {code}. Expires in {minutes} minutes. Do not share it with anyone.'),
        'message_fallback' => env('LOGIN_OTP_MESSAGE_FALLBACK', 'KLAES-METRO: {code}'),
        // Same exemptions as KLAES LoginOtpService (usernames, emails; case-insensitive).
        'exempt_usernames' => ['USMANSCB'],
        'exempt_emails' => ['katsoltech@gmail.com', 'dict@gmail.com'],
    ],

    // Seeded/placeholder addresses never receive a code (KLAES list).
    'placeholder_email_domains' => [
        'example.com', 'example.org', 'example.net', 'example.edu', 'test.com', 'email.com', 'domain.com', 'localhost',
        'klaes.local', '.local', '.test', '.invalid', '.localdomain', '.example',
    ],

    // Placeholder phone numbers are never texted (KLAES: 08000000000).
    'placeholder_phones' => ['2348000000000'],
];
