<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

            $user = User::where('role', 'User')
                ->whereRaw('LOWER(name) = ?', [strtolower(trim($credentials['name']))])
                ->first();

            if (!$user || $user->pin !== $credentials['pin']) {
                throw ValidationException::withMessages([
                    'name' => 'Nama atau PIN salah.',
                ]);
            }

            session([
                'logged_in'     => true,
                'user_name'     => $user->name,
                'current_shift' => $credentials['shift'],
                'user_role'     => 'User',
            ]);

            $request->session()->regenerate();

            return redirect()->route('operator.home');

        } else {
            $credentials = $request->validate([
                'name' => 'required|string',
                'pin'  => 'required|string|min:4',
            ]);

            $user = User::where('role', 'Administrator')
                ->whereRaw('LOWER(name) = ?', [strtolower(trim($credentials['name']))])
                ->first();

            if (!$user || $user->pin !== $credentials['pin']) {
                throw ValidationException::withMessages([
                    'name' => 'Nama atau PIN salah.',
                ]);
            }

            session([
                'logged_in' => true,
                'user_name' => $user->name,
                'user_role' => 'Administrator',
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
