<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SysadminAuth
{
    /**
     * Reject the request with 403 if the sysadmin session flag is not set.
     * This is applied to every protected sysadmin route so each one is
     * individually guarded even if the controller's own checkAuth() is removed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('sysadmin_authed')) {
            if ($request->expectsJson()) {
                abort(403, 'Unauthenticated.');
            }
            return redirect('/sysadmin');
        }

        return $next($request);
    }
}
