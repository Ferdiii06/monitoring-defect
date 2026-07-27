<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;

class AccountController extends Controller
{
    /**
     * Show the form for creating a new account.
     */
    public function create()
    {
        // Enforce session authentication check
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        return view('add_account');
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {
        // Enforce session authentication check
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        // Validate form inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'pin' => 'required|string|size:6',
            'role' => 'required|string|in:Admin,User',
            'shift' => 'required_if:role,User|nullable|string|in:1A,1B,2A,2B',
        ]);

        try {
            $apiUrl = config('services.external_api.url');

            $payload = [
                'nama' => $request->name,
                'pin'  => $request->pin,
                'role' => $request->role,
                'shift' => $request->role === 'User' ? $request->shift : null,
            ];

            $response = Http::timeout(5)->post($apiUrl . '/users', $payload);

            if ($response->successful()) {
                // Log activity locally
                ActivityLog::create([
                    'waktu' => now(),
                    'user_name' => session('user_name', 'Admin QA'),
                    'jenis_aksi' => 'Create Account',
                    'aktivitas' => 'Membuat akun baru (' . $request->role . ') - User: ' . $request->name,
                    'jenis_defect' => 'none',
                    'ip_address' => $request->ip() ?? '127.0.0.1',
                ]);

                session()->flash('success', 'Akun "' . $request->name . '" berhasil dibuat!');
                return redirect()->route('dashboard');
            } else {
                Log::error('Add Account gagal - API response tidak sukses', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                // Extract error message from API if available
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Gagal membuat akun di server API eksternal.';

                // If there are validation errors from API, return them
                if (isset($errorData['errors'])) {
                    return back()->withErrors($errorData['errors'])->withInput();
                }

                return back()->withErrors(['apiError' => $errorMessage])->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Add Account gagal - Exception', [
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['apiError' => 'Gagal terhubung ke server API eksternal: ' . $e->getMessage()])->withInput();
        }
    }
}
