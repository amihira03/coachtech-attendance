<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\CorrectionRequestController;

Route::get('/', function () {
    return auth()->check() ? redirect('/attendance') : redirect('/login');
});

/**
 * 一般ユーザー（認証必須）
 */
Route::middleware('auth')->group(function () {
    // 出勤登録画面（画面表示＋打刻処理）
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'showList'])->name('attendance.list');

    // 勤怠詳細（詳細表示＋修正申請）
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'store'])->name('attendance.detail.store');

    /**
     * 申請一覧（一般／管理者で同一パス）
     * - GET /stamp_correction_request/list
     * ※ web.php で同一パスを2本定義できないため、
     *   Controller 側で is_admin 等を見て表示(blade)を出し分ける想定
     */
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'showList'])
        ->name('stamp_correction_request.list');
});

/**
 * 管理者（認証必須＋管理者権限）
 */
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // 日次勤怠一覧
    Route::get('/attendance/list', [AdminAttendanceController::class, 'showList'])
        ->name('admin.attendance.list');

    // 勤怠詳細（詳細表示＋修正）
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.show');
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    // スタッフ一覧
    Route::get('/staff/list', [AdminStaffController::class, 'showList'])
        ->name('admin.staff.list');

    // スタッフ別勤怠一覧（月次）
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'showStaffList'])
        ->name('admin.attendance.staff');
});

/**
 * 修正申請承認（管理者）
 * ※ 基本設計書では /stamp_correction_request/approve/{attendance_correct_request_id}
 *    のため、prefix('admin') の外に置く
 */
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [CorrectionRequestController::class, 'show']
    )->name('stamp_correction_request.approve.show');

    Route::post(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [CorrectionRequestController::class, 'confirm']
    )->name('stamp_correction_request.approve.confirm');
});
