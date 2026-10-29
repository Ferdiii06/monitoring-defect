<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\RoleRouteHelper;

class EnsureIsRework
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('logged_in')) {
            return RoleRouteHelper::redirectForRole(null);
        }

        if (!in_array(session('user_role'), ['Rework', 'Administrator'], true)) {
            return RoleRouteHelper::redirectForRole();
        }

        return $next($request);
    }
}
