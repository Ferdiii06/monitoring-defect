<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;

class AccountController extends Controller
{
    /**
     * Display a listing of users and the create form.
     */
    public function index()
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $users = User::orderBy('name')->get();

        return view('add_account', compact('users'));
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $pinRule = $request->role === 'Administrator'
            ? 'required|string|min:4'
            : 'required|digits:6';

        $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'pin'  => $pinRule,
            'role' => 'required|string|in:Administrator,User,Rework',
        ]);

        User::forceCreate([
            'name' => $request->name,
            'pin'  => $request->pin,
            'role' => $request->role,
        ]);

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin QA'),
            'jenis_aksi'   => 'Create Account',
            'aktivitas'    => 'Membuat akun baru (' . $request->role . ') - User: ' . $request->name,
            'jenis_defect' => 'none',
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('account.create')->with('success', 'Akun "' . $request->name . '" berhasil dibuat!');
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, $id)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($id);

        $isNonAdmin = in_array($request->role, ['User', 'Rework'], true);
        $oldPinIsSixDigits = is_string($user->pin) && preg_match('/^\d{6}$/', (string) $user->pin) === 1;

        if ($request->role === 'Administrator') {
            $pinRule = 'nullable|string|min:4';
        } elseif ($request->role !== $user->role && $isNonAdmin && !$oldPinIsSixDigits) {
            $pinRule = 'required|digits:6';
        } else {
            $pinRule = 'nullable|digits:6';
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $id,
            'pin'  => $pinRule,
            'role' => 'required|string|in:Administrator,User,Rework',
        ]);

        $updateData = [
            'name' => $request->name,
            'role' => $request->role,
        ];

        if ($request->filled('pin')) {
            $updateData['pin'] = $request->pin;
        }

        $user->forceFill($updateData)->save();

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin QA'),
            'jenis_aksi'   => 'Update Account',
            'aktivitas'    => 'Memperbarui akun (' . $request->role . ') - User: ' . $user->name,
            'jenis_defect' => 'none',
            'ip_address'   => $request->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('account.create')->with('success', 'Akun "' . $user->name . '" berhasil diperbarui!');
    }

    /**
     * Remove the specified account.
     */
    public function destroy($id)
    {
        if (!session('logged_in')) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($id);
        $userName = $user->name;
        $userRole = $user->role;

        $user->delete();

        ActivityLog::create([
            'waktu'        => now(),
            'user_name'    => session('user_name', 'Admin QA'),
            'jenis_aksi'   => 'Delete Account',
            'aktivitas'    => 'Menghapus akun (' . $userRole . ') - User: ' . $userName,
            'jenis_defect' => 'none',
            'ip_address'   => request()->ip() ?? '127.0.0.1',
        ]);

        return redirect()->route('account.create')->with('success', 'Akun "' . $userName . '" berhasil dihapus!');
    }
}
