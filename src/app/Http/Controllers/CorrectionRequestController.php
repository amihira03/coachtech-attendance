<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CorrectionRequestController extends Controller
{
    public function showList(): View
    {
        // 申請一覧は一般/管理者で同一URLのため、ここでviewを出し分け
        if (auth()->user()?->is_admin) {
            return view('admin.corrections.list');
        }

        return view('corrections.list');
    }

    public function show(int $attendance_correct_request_id): View
    {
        // 仮：承認画面表示
        return view('admin.corrections.approve', [
            'attendance_correct_request_id' => $attendance_correct_request_id,
        ]);
    }

    public function confirm(int $attendance_correct_request_id): RedirectResponse
    {
        // 仮：承認処理は後で実装（ひとまず一覧へ戻す）
        return redirect()->route('stamp_correction_request.list');
    }
}
