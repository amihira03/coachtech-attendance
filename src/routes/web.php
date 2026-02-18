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
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::get('/attendance/list', [AttendanceController::class, 'showList'])->name('attendance.list');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'storeDetail'])
        ->name('attendance.detail.store');

    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'showList'])
        ->name('stamp_correction_request.list');
});

// 管理者ログイン画面（表示のみ）
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login');

/**
 * 管理者（認証必須＋管理者権限）
 */
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'showList'])
        ->name('admin.attendance.list');

    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('admin.attendance.show');
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])
        ->name('admin.attendance.update');

    Route::get('/staff/list', [AdminStaffController::class, 'showList'])
        ->name('admin.staff.list');

    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'showStaffList'])
        ->name('admin.attendance.staff');
    Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportStaffCsv'])
        ->name('admin.attendance.staff.csv');
});

/**
 * 修正申請承認（管理者）
 */
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [CorrectionRequestController::class, 'show']
    )->name('stamp_correction_request.approve.show');

    Route::post(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [CorrectionRequestController::class, 'confirm']
    )->name('stamp_correction_request.approve.confirm');
});
