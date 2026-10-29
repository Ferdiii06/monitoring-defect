<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use Illuminate\Http\Request;

class ReworkController extends Controller
{
    /**
     * Display a read-only list of rework defects submitted by the active rework user.
     */
    public function index(Request $request)
    {
        $userName = session('user_name');

        $defects = Defect::where('jenis_assy', 'Final Assy')
            ->whereNotNull('rework_group_id')
            ->where('user_name', $userName)
            ->orderByDesc('waktu')
            ->paginate(20);

        return view('rework.index', compact('defects'));
    }
}
