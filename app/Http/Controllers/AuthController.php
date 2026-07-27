<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    /**
     * Handle authentication attempt statically.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string',
            'pin' => 'required|string|size:6',
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

                // Find user by matching name and pin
                $matchedUser = collect($users)->first(function ($user) use ($credentials) {
                    $userName = $user['nama'] ?? $user['name'] ?? '';
                    $userPin = (string)($user['pin'] ?? '');
                    return strcasecmp(trim($userName), trim($credentials['name'])) === 0 && $userPin === trim($credentials['pin']);
                });

                if ($matchedUser) {
                    $role = strtolower($matchedUser['role'] ?? '');
                    if ($role === 'admin') {
                        session([
                            'logged_in' => true,
                            'user_name' => $matchedUser['nama'] ?? $matchedUser['name'] ?? $credentials['name'],
                            'user_role' => 'Administrator'
                        ]);

                        $request->session()->regenerate();

                        return redirect()->intended(route('dashboard'));
                    } else {
                        throw ValidationException::withMessages([
                            'loginError' => 'Akun dengan role User tidak memiliki akses ke Dashboard.',
                        ]);
                    }
                }
            }
        } catch (ValidationException $ve) {
            throw $ve;
        } catch (\Exception $e) {
            Log::warning('Login: External API check failed - ' . $e->getMessage());
        }

        throw ValidationException::withMessages([
            'loginError' => 'Nama atau PIN yang Anda masukkan salah.',
        ]);
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
