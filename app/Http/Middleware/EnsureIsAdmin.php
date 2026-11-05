<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\RoleRouteHelper;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('logged_in')) {
            return RoleRouteHelper::redirectForRole(null);
        }

        if (session('user_role') !== 'Administrator') {
            if ($request->expectsJson() || $request->isMethod('delete')) {
                abort(403, 'Akses tidak diizinkan.');
            }
            return RoleRouteHelper::redirectForRole();
        }

        return $next($request);
    }
}
