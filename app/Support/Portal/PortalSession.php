<?php

namespace App\Support\Portal;

use App\Models\Portal\PortalApplicant;
use Illuminate\Http\Request;

/**
 * The applicant signed in to the KAMMA applicant portal.
 *
 * Real accounts (kamma_portal_applicants, since 2026-11-25) keep only their id in the session and are read again on
 * every request, so a profile change shows at once and a switched-off account is signed out. The demo applicant
 * (config kamma.portal.demo) is session only and sees sample data; nothing it does is saved.
 * Nothing here touches KLAES dbo.users.
 */
class PortalSession
{
    public const KEY = 'kamma_portal_applicant';

    /** A registration waiting for its phone code. */
    public const PENDING = 'kamma_portal_pending_registration';

    private const RESOLVED = 'kamma_portal_resolved';

    /** @return array{id?: int, username: string, name: string, phone: ?string, email: ?string, is_demo: bool, since: ?string}|null */
    public static function applicant(?Request $request = null): ?array
    {
        $request ??= request();
        if (! $request->hasSession()) {
            return null;
        }
        if ($request->attributes->has(self::RESOLVED)) {
            return $request->attributes->get(self::RESOLVED);
        }

        $stored = $request->session()->get(self::KEY);
        $applicant = null;
        if (is_array($stored) && ! empty($stored['is_demo']) && filled($stored['username'] ?? null)) {
            $applicant = $stored;
        } elseif (is_array($stored) && ! empty($stored['id'])) {
            $account = PortalApplicant::query()->whereKey((int) $stored['id'])->where('is_active', true)->first();
            $applicant = $account?->toSession();
            if ($account === null) {
                $request->session()->forget(self::KEY);
            }
        }

        $request->attributes->set(self::RESOLVED, $applicant);

        return $applicant;
    }

    /** The signed-in account, or null for a guest or the demo applicant. */
    public static function account(?Request $request = null): ?PortalApplicant
    {
        $id = self::applicant($request)['id'] ?? null;

        return $id ? PortalApplicant::query()->find($id) : null;
    }

    public static function isDemo(?Request $request = null): bool
    {
        return (bool) (self::applicant($request)['is_demo'] ?? false);
    }

    public static function signInAccount(Request $request, PortalApplicant $account): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::KEY, ['id' => $account->id]);
        $request->attributes->remove(self::RESOLVED);
        $account->forceFill(['last_login_at' => now()])->save();
    }

    /** The demo applicant (session only). */
    public static function signInDemo(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->put(self::KEY, self::demoAccount() + ['is_demo' => true, 'since' => now()->toDateTimeString()]);
        $request->attributes->remove(self::RESOLVED);
    }

    /** Forget the per-request copy after the account itself changed (profile update). */
    public static function refresh(Request $request): void
    {
        $request->attributes->remove(self::RESOLVED);
    }

    public static function signOut(Request $request): void
    {
        $request->session()->forget([self::KEY, self::PENDING]);
        $request->attributes->remove(self::RESOLVED);
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
