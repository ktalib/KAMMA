<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\PortalApplicant;
use App\Support\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Applicant portal: My profile. Saves to kamma_portal_applicants (since 2026-11-25); the demo applicant's forms are
 * checked and answer "Saved (preview)". The mobile number is the sign-in name and cannot be changed here yet
 * (that needs a code to the new number, as on the KLAES LAAS portal).
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('portal.profile', ['applicant' => PortalSession::applicant($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $account = PortalSession::account($request);
        $request->merge([
            'name' => is_string($request->input('name')) ? Str::title(trim(preg_replace('/\s+/u', ' ', $request->input('name')))) : $request->input('name'),
            'email' => is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : $request->input('email'),
        ]);
        $data = $request->validateWithBag('details', [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique(PortalApplicant::class, 'email')->ignore($account?->id)],
        ], ['email.unique' => 'Another account already uses this email.']);

        if ($account === null) {
            return back()->with('status', 'Saved (preview): your details were checked but not stored.');
        }

        $account->update(['name' => $data['name'], 'email' => $data['email'] ?? null]);
        PortalSession::refresh($request);

        return back()->with('status', 'Your details were saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $account = PortalSession::account($request);
        $request->validateWithBag('password', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed', 'different:current_password'],
        ]);

        if ($account === null) {
            return back()->with('status', 'Saved (preview): the new password was checked but not stored.');
        }
        if (! Hash::check((string) $request->input('current_password'), $account->password)) {
            return back()->withErrors(['current_password' => 'Your current password is not right.'], 'password');
        }

        $account->update(['password' => (string) $request->input('password')]);

        return back()->with('status', 'Your password was changed.');
    }
}
