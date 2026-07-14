<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsLoggedIn
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('logged_in')) {
            // Store the intended URL so they can be redirected back after login
            session()->put('url.intended', $request->fullUrl());
            return redirect()->route('login');
        }

        return $next($request);
    }
}
