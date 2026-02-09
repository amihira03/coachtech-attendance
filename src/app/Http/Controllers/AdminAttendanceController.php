<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    public function showList(): View
    {
        return view('admin.attendance.list');
    }

    public function show(int $id): View
    {
        return view('admin.attendance.show', ['id' => $id]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        // 仮：勤怠修正は後で実装
        return redirect()->route('admin.attendance.show', ['id' => $id]);
    }

    public function showStaffList(int $id): View
    {
        // 仮：スタッフ別勤怠一覧（月次）
        return view('admin.attendance.staff', ['id' => $id]);
    }
}
