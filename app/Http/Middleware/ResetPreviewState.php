<?php

namespace App\Http\Middleware;

use App\Support\Preview;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Every request starts out of preview; PreviewWhenNotMigrated turns it on for the screens that need it. */
class ResetPreviewState
{
    public function handle(Request $request, Closure $next): Response
    {
        Preview::reset();
        PreviewWhenNotMigrated::flush();

        return $next($request);
    }
}
