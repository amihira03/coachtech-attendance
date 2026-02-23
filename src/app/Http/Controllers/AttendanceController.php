<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Http\Requests\AttendanceCorrectionByDateRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Services\Attendance\AttendanceIndexService;
use App\Services\Attendance\AttendanceShowService;
use App\Services\Attendance\CreateCorrectionRequestByDateService;
use App\Services\Attendance\CreateCorrectionRequestService;
use App\Services\Attendance\MonthlyAttendanceService;
use App\Services\Attendance\StampAttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(AttendanceIndexService $service): View
    {
        $userId = auth()->id();
        $now = CarbonImmutable::now();

        return view('attendance.index', $service->build((int) $userId, $now));
    }

    public function store(Request $request, StampAttendanceService $service): RedirectResponse
    {
        $action = (string) $request->input('action');
        $userId = (int) auth()->id();
        $now = CarbonImmutable::now();

        $service->handle($userId, $action, $now);

        if ($action === 'clock_out') {
            return redirect()
                ->route('attendance.index')
                ->with('message', 'お疲れ様でした。');
        }

        return redirect()->route('attendance.index');
    }

    public function showList(Request $request, MonthlyAttendanceService $service): View
    {
        $userId = (int) auth()->id();
        $now = CarbonImmutable::now();
        $month = (string) $request->query('month', '');

        return view('attendance.list', $service->build($userId, $month, $now));
    }

    public function show(int $id, AttendanceShowService $service): View
    {
        $userId = (int) auth()->id();
        $userName = (string) (auth()->user()?->name ?? '');

        return view('attendance.show', $service->build($userId, $id, $userName));
    }

    public function showByDate(Request $request): RedirectResponse
    {
        $date = (string) $request->query('date', '');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            abort(404);
        }

        $userId = (int) auth()->id();

        $attendance = Attendance::query()->firstOrCreate(
            [
                'user_id' => $userId,
                'work_date' => $date,
            ],
            [
                'status' => Attendance::STATUS_OFF_DUTY,
            ]
        );

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }

    public function storeDetail(
        AttendanceCorrectionRequest $request,
        int $id,
        CreateCorrectionRequestService $service
    ): RedirectResponse {
        $userId = (int) auth()->id();

        $result = $service->handle($userId, $id, $request->validated());

        return redirect()->route('attendance.detail', ['id' => $result['attendanceId']]);
    }

    public function storeByDate(
        AttendanceCorrectionByDateRequest $request,
        CreateCorrectionRequestByDateService $service
    ): RedirectResponse {
        $userId = (int) auth()->id();
        $date = (string) $request->input('date');

        $attendanceId = $service->handle($userId, $date, $request->validated());

        return redirect()->route('attendance.detail', ['id' => $attendanceId]);
    }
}
