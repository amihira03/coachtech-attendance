<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(): View
    {
        return view('attendance.index');
    }

    public function store(Request $request, ?int $id = null): RedirectResponse
    {
        // 仮：打刻 or 修正申請の保存は後で実装
        // どこからPOSTされたかで戻り先を分けます
        if ($id !== null) {
            return redirect()->route('attendance.detail', ['id' => $id]);
        }

        return redirect()->route('attendance.index');
    }

    public function showList(): View
    {
        return view('attendance.list');
    }

    public function show(int $id): View
    {
        // 仮：詳細表示用にIDだけ渡しておく（Blade側で表示確認できる）
        return view('attendance.show', ['id' => $id]);
    }
}
