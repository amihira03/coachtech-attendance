<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\Attendance\MonthlyAttendanceService;
use App\Services\Attendance\CreateCorrectionRequestService;
use App\Services\Attendance\StampAttendanceService;

class AttendanceController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();
        $now = CarbonImmutable::now();
        $today = $now->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        $status = $attendance?->status ?? Attendance::STATUS_OFF_DUTY;

        $statusText = match ($status) {
            Attendance::STATUS_OFF_DUTY => '勤務外',
            Attendance::STATUS_WORKING => '出勤中',
            Attendance::STATUS_ON_BREAK => '休憩中',
            Attendance::STATUS_FINISHED => '退勤済',
            default => '不明',
        };

        return view('attendance.index', [
            'now' => $now,
            'attendance' => $attendance,
            'status' => $status,
            'statusText' => $statusText,

            'isFinished' => $status === Attendance::STATUS_FINISHED,

            'showClockIn' => $status === Attendance::STATUS_OFF_DUTY,
            'showBreakStart' => $status === Attendance::STATUS_WORKING,
            'showBreakEnd' => $status === Attendance::STATUS_ON_BREAK,
            'showClockOut' => $status === Attendance::STATUS_WORKING,
        ]);
    }

    public function store(Request $request, StampAttendanceService $service): RedirectResponse
    {
        $action = (string) $request->input('action');
        $userId = auth()->id();
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
        $userId = auth()->id();
        $now = CarbonImmutable::now();

        $month = (string) $request->query('month', '');

        $data = $service->build($userId, $month, $now);

        return view('attendance.list', $data);
    }

    public function show(int $id): View
    {
        $userId = auth()->id();

        $attendance = Attendance::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->with(['breaks'])
            ->firstOrFail();

        $pendingCorrectionRequest = CorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('requested_by', $userId)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->with(['breaks'])
            ->latest('id')
            ->first();

        $isPending = $pendingCorrectionRequest !== null;

        $display = $isPending
            ? [
                'clockIn' => $this->formatTime($pendingCorrectionRequest->requested_clock_in_at),
                'clockOut' => $this->formatTime($pendingCorrectionRequest->requested_clock_out_at),
                'note' => (string) $pendingCorrectionRequest->requested_note,
                'breakRows' => $this->mapCorrectionRequestBreakRows($pendingCorrectionRequest),
            ]
            : [
                'clockIn' => $this->formatTime($attendance->clock_in_at),
                'clockOut' => $this->formatTime($attendance->clock_out_at),
                'note' => (string) ($attendance->note ?? ''),
                'breakRows' => $this->mapAttendanceBreakRows($attendance),
            ];

        return view('attendance.show', [
            'attendance' => $attendance,
            'attendanceId' => $attendance->id,
            'userName' => (string) (auth()->user()?->name ?? ''),
            'displayDateLabel' => $this->formatDateLabel($attendance->work_date),

            'isPending' => $isPending,
            'pendingRequest' => $pendingCorrectionRequest, // Bladeで必要なら使える
            'display' => $display,
        ]);
    }

    public function storeDetail(
        AttendanceCorrectionRequest $request,
        int $id,
        CreateCorrectionRequestService $service
    ): RedirectResponse {
        $userId = auth()->id();

        $result = $service->handle($userId, $id, $request->validated());

        return redirect()->route('attendance.detail', ['id' => $result['attendanceId']]);
    }

    private function formatTime(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return CarbonImmutable::parse($value)->format('H:i');
    }

    private function formatDateLabel(mixed $workDate): string
    {
        $date = CarbonImmutable::parse($workDate);
        return $date->format('Y/m/d') . '(' . $this->weekdayJa($date->dayOfWeek) . ')';
    }

    private function weekdayJa(int $dayOfWeek): string
    {
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        return $weekdays[$dayOfWeek] ?? '';
    }

    private function mapAttendanceBreakRows(Attendance $attendance): array
    {
        $rows = [];

        foreach ($attendance->breaks as $break) {
            $rows[] = [
                'start' => $this->formatTime($break->break_start_at),
                'end' => $this->formatTime($break->break_end_at),
            ];
        }

        $rows[] = ['start' => '', 'end' => ''];

        return $rows;
    }

    private function mapCorrectionRequestBreakRows(CorrectionRequest $correctionRequest): array
    {
        $rows = [];

        foreach ($correctionRequest->breaks as $break) {
            $rows[] = [
                'start' => $this->formatTime($break->break_start_at),
                'end' => $this->formatTime($break->break_end_at),
            ];
        }

        return $rows;
    }
}
