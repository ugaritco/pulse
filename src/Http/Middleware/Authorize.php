<?php

namespace Ugarit\Pulse\Http\Middleware;

use Closure;
use Heritage\Contracts\Auth\Access\Gate;
use Heritage\Http\Request;

class Authorize
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected Gate $gate)
    {
        //
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->gate->authorize('viewPulse');

        return $next($request);
    }
}
