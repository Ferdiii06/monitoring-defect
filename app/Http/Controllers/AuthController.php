<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

            return redirect()->intended('/dashboard');
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

        return redirect('/');
    }
}
