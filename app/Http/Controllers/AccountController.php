<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Show the form for creating a new account.
     */
    public function create()
    {
        // Enforce session authentication check
        if (!session('logged_in')) {
            return redirect('/');
        }

        return view('add_account');
    }

    /**
     * Store a newly created account statically (session-only).
     */
    public function store(Request $request)
    {
        // Enforce session authentication check
        if (!session('logged_in')) {
            return redirect('/');
        }

        // Validate form inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'pin' => 'required|string|size:6',
            'shift' => 'required|string',
            'jabatan' => 'required|string',
        ]);

        // Statically trigger success flash message (no database insertion)
        session()->flash('success', 'Akun "' . $request->name . '" berhasil dibuat!');

        return redirect('/dashboard');
    }
}
