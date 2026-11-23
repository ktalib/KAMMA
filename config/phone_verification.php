<?php

return [
    'enabled' => env('PHONE_VERIFICATION_ENABLED', true),
    'code_length' => env('PHONE_VERIFICATION_CODE_LENGTH', 6),
    'ttl_minutes' => env('PHONE_VERIFICATION_TTL_MINUTES', 10),
    'resend_cooldown_seconds' => env('PHONE_VERIFICATION_RESEND_SECONDS', 60),
];
