<?php

namespace App\Support\Portal;

use Illuminate\Http\Request;

/**
 * The applicant signed in to the KAMMA applicant portal.
 *
 * UI first (user 2026-11-23): applicant accounts are not stored yet, so the signed-in applicant lives in the session
 * only. The demo applicant comes from config('kamma.portal.demo'); an account made on the Register screen lasts for the
 * browser session. Nothing here touches KLAES dbo.users or any table. When the backend is built this becomes a real
 * guard over kamma_portal_applicants (draft migration in database/migrations/portal/).
 */
class PortalSession
{
    public const KEY = 'kamma_portal_applicant';

    /** A registration waiting for its phone code (preview). */
    public const PENDING = 'kamma_portal_pending_registration';

    /** @return array{username: string, name: string, phone: ?string, email: ?string, is_demo: bool, since: string}|null */
    public static function applicant(?Request $request = null): ?array
    {
        $request ??= request();
        if (! $request->hasSession()) {
            return null;
        }
        $applicant = $request->session()->get(self::KEY);

        return is_array($applicant) && filled($applicant['username'] ?? null) ? $applicant : null;
    }

    public static function isDemo(?Request $request = null): bool
    {
        return (bool) (self::applicant($request)['is_demo'] ?? false);
    }

    /** @param array{username: string, name: string, phone: ?string, email: ?string} $account */
    public static function signIn(Request $request, array $account, bool $demo): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::KEY, [
            'username' => $account['username'],
            'name' => $account['name'],
            'phone' => $account['phone'] ?? null,
            'email' => $account['email'] ?? null,
            'is_demo' => $demo,
            'since' => now()->toDateTimeString(),
        ]);
    }

    public static function signOut(Request $request): void
    {
        $request->session()->forget([self::KEY, self::PENDING]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }

    /** @return array{username: string, name: string, phone: ?string, email: ?string} */
    public static function demoAccount(): array
    {
        $demo = (array) config('kamma.portal.demo');

        return [
            'username' => (string) ($demo['username'] ?? 'demo.applicant'),
            'name' => (string) ($demo['name'] ?? 'Demo Applicant'),
            'phone' => $demo['phone'] ?? null,
            'email' => $demo['email'] ?? null,
        ];
    }
}
