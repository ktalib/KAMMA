<?php

namespace App\Http\Middleware;

use App\Support\Portal\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Applicant portal pages: only for a signed-in applicant; anyone else goes to the portal sign-in (not the staff login). */
class PortalAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (PortalSession::applicant($request) === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sign in to the applicant portal first.'], 401);
            }

            return redirect()->guest(route('portal.login'));
        }

        return $next($request);
    }
}
