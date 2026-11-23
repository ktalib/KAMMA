<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Applicant portal: My profile. UI first: the forms are checked and answer "Saved (preview)"; nothing is stored.
 * Changing the phone number will need a code to the new number (as on the KLAES LAAS portal) once SMS is wired.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('portal.profile', ['applicant' => PortalSession::applicant($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => is_string($request->input('name')) ? Str::title(trim(preg_replace('/\s+/u', ' ', $request->input('name')))) : $request->input('name'),
            'email' => is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : $request->input('email'),
        ]);
        $request->validateWithBag('details', [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        return back()->with('status', 'Saved (preview): your details were checked but not stored.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed', 'different:current_password'],
        ]);

        return back()->with('status', 'Saved (preview): the new password was checked but not stored.');
    }
}
