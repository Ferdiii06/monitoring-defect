<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InspectProcessTypeController extends Controller
{
    private const REDIRECT_MESSAGE = 'Master ini sudah dipindahkan ke Area Ditemukan Pre Assy';

    public function index()
    {
        return redirect()->route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy'])
            ->with('info', self::REDIRECT_MESSAGE)
            ->with('success', self::REDIRECT_MESSAGE);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy'])
            ->with('info', self::REDIRECT_MESSAGE)
            ->with('success', self::REDIRECT_MESSAGE);
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy'])
            ->with('info', self::REDIRECT_MESSAGE)
            ->with('success', self::REDIRECT_MESSAGE);
    }

    public function destroy($id)
    {
        return redirect()->route('admin.master.area_ditemukans.index', ['kategori' => 'Pre Assy'])
            ->with('info', self::REDIRECT_MESSAGE)
            ->with('success', self::REDIRECT_MESSAGE);
    }
}
