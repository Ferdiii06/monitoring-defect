<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    /**
     * Handle authentication attempt for both User and Admin.
     */
    public function login(Request $request)
    {
        $role = $request->input('login_type', 'user');

        if ($role === 'user') {
            $credentials = $request->validate([
                'name'  => 'required|string',
                'shift' => 'required|string|in:1A,1B,2A,2B',
                'pin'   => 'required|string|size:6',
            ]);

            session([
                'logged_in'     => true,
                'user_name'     => $credentials['name'],
                'current_shift' => $credentials['shift'],
                'user_role'     => 'User',
            ]);

            $request->session()->regenerate();

            return redirect()->route('operator.home');

        } else {
            $credentials = $request->validate([
                'name' => 'required|string',
                'pin'  => 'required|string|size:6',
            ]);

            // Static validation: Name matches "Admin QA" and PIN matches "123456"
            if ($credentials['name'] === 'Admin QA' && $credentials['pin'] === '123456') {
                session([
                    'logged_in' => true,
                    'user_name' => $credentials['name'],
                    'user_role' => 'Administrator'
                ]);

                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }

            // Check external API for registered Admin users
            try {
                $apiUrl = config('services.external_api.url');
                $response = Http::timeout(5)->get($apiUrl . '/users');

                if ($response->successful()) {
                    $users = $response->json('data') ?? [];

                    $matchedUser = collect($users)->first(function ($user) use ($credentials) {
                        $userName = $user['nama'] ?? $user['name'] ?? '';
                        $userPin = (string)($user['pin'] ?? '');
                        return strcasecmp(trim($userName), trim($credentials['name'])) === 0 && $userPin === trim($credentials['pin']);
                    });

                    if ($matchedUser) {
                        session([
                            'logged_in' => true,
                            'user_name' => $matchedUser['nama'] ?? $matchedUser['name'] ?? $credentials['name'],
                            'user_role' => 'Administrator'
                        ]);

                        $request->session()->regenerate();

                        return redirect()->intended(route('dashboard'));
                    }
                }
            } catch (ValidationException $ve) {
                throw $ve;
            } catch (\Exception $e) {
                Log::warning('Login: External API check failed - ' . $e->getMessage());
            }

            // Allow fallback login for any admin input if required or show error
            session([
                'logged_in' => true,
                'user_name' => $credentials['name'],
                'user_role' => 'Administrator'
            ]);

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }
    }


    /**
     * Log the user out of the application using session forget.
     */
    public function logout(Request $request)
    {
        session()->forget(['logged_in', 'user_name', 'user_role']);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
