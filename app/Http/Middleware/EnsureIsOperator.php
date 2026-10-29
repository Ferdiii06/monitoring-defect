<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsOperator
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array(session('user_role'), ['User', 'Rework'], true)) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
