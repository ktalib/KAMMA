<?php

namespace App\Http\Middleware;

use App\Support\Portal\PortalSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Portal sign-in and register screens: an applicant who is already signed in goes to the portal dashboard. */
class PortalGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (PortalSession::applicant($request) !== null) {
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
