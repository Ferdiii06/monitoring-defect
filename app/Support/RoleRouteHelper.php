<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

class RoleRouteHelper
{
    /**
     * Get the home route name for a given role (or currently logged-in role).
     *
     * - Administrator => 'dashboard'
     * - User          => 'operator.home'
     * - Rework        => 'rework.index'
     * - Guest/default => 'login'
     */
    public static function homeRouteName(?string $role = null): string
    {
        if ($role === null) {
            $role = session('logged_in') ? session('user_role') : null;
        }

        return match ($role) {
            'Administrator' => 'dashboard',
            'User'          => 'operator.home',
            'Rework'        => 'rework.index',
            default         => 'login',
        };
    }

    /**
     * Redirect to the home route for the given role (or session role / guest).
     */
    public static function redirectForRole(?string $role = null, int $status = 302, array $headers = []): Response
    {
        return redirect()->route(static::homeRouteName($role), [], $status, $headers);
    }
}
