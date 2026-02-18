<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use App\Http\Requests\AttendanceCorrectionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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

            // ★追加：退勤済み判定（ヘッダー表示切替用）
            'isFinished' => $status === Attendance::STATUS_FINISHED,

            // ボタン出し分け（Blade側はこれだけ見ればOK）
            'showClockIn' => $status === Attendance::STATUS_OFF_DUTY,
            'showBreakStart' => $status === Attendance::STATUS_WORKING,
            'showBreakEnd' => $status === Attendance::STATUS_ON_BREAK,
            'showClockOut' => $status === Attendance::STATUS_WORKING,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $action = (string) $request->input('action');
        $userId = auth()->id();
        $now = CarbonImmutable::now();
        $today = $now->toDateString();

        return DB::transaction(function () use ($action, $userId, $now, $today) {
            $attendance = Attendance::query()
                ->where('user_id', $userId)
                ->where('work_date', $today)
                ->lockForUpdate()
                ->first();

            $currentStatus = $attendance?->status ?? Attendance::STATUS_OFF_DUTY;

            if ($action === 'clock_in') {
                if ($currentStatus !== Attendance::STATUS_OFF_DUTY) {
                    return redirect()->route('attendance.index');
                }

                $attendance = $attendance ?? new Attendance([
                    'user_id' => $userId,
                    'work_date' => $today,
                ]);

                if ($attendance->clock_in_at !== null) {
                    return redirect()->route('attendance.index');
                }

                $attendance->clock_in_at = $now;
                $attendance->status = Attendance::STATUS_WORKING;
                $attendance->save();

                return redirect()->route('attendance.index');
            }

            if ($action === 'break_start') {
                if ($currentStatus !== Attendance::STATUS_WORKING || $attendance === null) {
                    return redirect()->route('attendance.index');
                }

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $now,
                ]);

                $attendance->status = Attendance::STATUS_ON_BREAK;
                $attendance->save();

                return redirect()->route('attendance.index');
            }

            if ($action === 'break_end') {
                if ($currentStatus !== Attendance::STATUS_ON_BREAK || $attendance === null) {
                    return redirect()->route('attendance.index');
                }

                $break = BreakTime::query()
                    ->where('attendance_id', $attendance->id)
                    ->whereNull('break_end_at')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($break === null) {
                    return redirect()->route('attendance.index');
                }

                $break->break_end_at = $now;
                $break->save();

                $attendance->status = Attendance::STATUS_WORKING;
                $attendance->save();

                return redirect()->route('attendance.index');
            }

            if ($action === 'clock_out') {
                if ($currentStatus !== Attendance::STATUS_WORKING || $attendance === null) {
                    return redirect()->route('attendance.index');
                }

                if ($attendance->clock_out_at !== null) {
                    return redirect()->route('attendance.index');
                }

                $attendance->clock_out_at = $now;
                $attendance->status = Attendance::STATUS_FINISHED;
                $attendance->save();

                return redirect()
                    ->route('attendance.index')
                    ->with('message', 'お疲れ様でした。');
            }

            return redirect()->route('attendance.index');
        });
    }

    public function showList(Request $request): View
    {
        $userId = auth()->id();
        $now = CarbonImmutable::now();

        $month = (string) $request->query('month', '');
        $base = preg_match('/^\d{4}-\d{2}$/', $month) === 1
            ? CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()
            : $now->startOfMonth();

        $start = $base->startOfMonth();
        $end = $base->endOfMonth();

        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        // work_date => Attendance
        $attendanceByDate = [];
        $attendanceIds = [];
        foreach ($attendances as $attendance) {
            $workDate = $attendance->work_date instanceof \DateTimeInterface
                ? CarbonImmutable::parse($attendance->work_date)->toDateString()
                : (string) $attendance->work_date;

            $attendanceByDate[$workDate] = $attendance;
            $attendanceIds[] = $attendance->id;
        }

        // attendance_id => BreakTime[]
        $breaksByAttendanceId = [];
        if (!empty($attendanceIds)) {
            $breaks = BreakTime::query()
                ->whereIn('attendance_id', $attendanceIds)
                ->get();

            foreach ($breaks as $break) {
                $breaksByAttendanceId[$break->attendance_id][] = $break;
            }
        }

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        $rows = [];
        $cursor = $start;
        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $attendance = $attendanceByDate[$date] ?? null;

            $clockIn = '';
            $clockOut = '';
            $breakTotal = '';
            $workTotal = '';
            $attendanceId = null;

            if ($attendance !== null) {
                $attendanceId = $attendance->id;

                if ($attendance->clock_in_at !== null) {
                    $clockIn = CarbonImmutable::parse($attendance->clock_in_at)->format('H:i');
                }
                if ($attendance->clock_out_at !== null) {
                    $clockOut = CarbonImmutable::parse($attendance->clock_out_at)->format('H:i');
                }

                $breakSeconds = 0;
                $breaks = $breaksByAttendanceId[$attendance->id] ?? [];
                foreach ($breaks as $break) {
                    if ($break->break_start_at === null || $break->break_end_at === null) {
                        continue;
                    }

                    $startAt = CarbonImmutable::parse($break->break_start_at);
                    $endAt = CarbonImmutable::parse($break->break_end_at);

                    if ($endAt->lessThan($startAt)) {
                        continue;
                    }

                    $breakSeconds += $startAt->diffInSeconds($endAt);
                }

                if ($breakSeconds > 0) {
                    $breakTotal = $this->formatDuration($breakSeconds);
                }

                if ($attendance->clock_in_at !== null && $attendance->clock_out_at !== null) {
                    $clockInAt = CarbonImmutable::parse($attendance->clock_in_at);
                    $clockOutAt = CarbonImmutable::parse($attendance->clock_out_at);

                    if ($clockOutAt->gte($clockInAt)) {
                        $workSeconds = $clockInAt->diffInSeconds($clockOutAt) - $breakSeconds;
                        if ($workSeconds < 0) {
                            $workSeconds = 0;
                        }
                        $workTotal = $this->formatDuration($workSeconds);
                    }
                }
            }

            $rows[] = [
                'dateLabel' => $cursor->format('m/d') . '(' . $weekdays[$cursor->dayOfWeek] . ')',
                'clockIn' => $clockIn,
                'clockOut' => $clockOut,
                'breakTotal' => $breakTotal,
                'workTotal' => $workTotal,
                'attendanceId' => $attendanceId,
            ];

            $cursor = $cursor->addDay();
        }

        return view('attendance.list', [
            'monthLabel' => $base->format('Y/m'),
            'prevMonth' => $base->subMonth()->format('Y-m'),
            'nextMonth' => $base->addMonth()->format('Y-m'),
            'rows' => $rows,
        ]);
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
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

    public function storeDetail(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        $userId = auth()->id();

        $attendance = Attendance::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // 二重申請ガード（承認待ちがあるなら作らない）
        $alreadyPending = CorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('requested_by', $userId)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($alreadyPending) {
            return redirect()->route('attendance.detail', ['id' => $attendance->id]);
        }

        $workDate = CarbonImmutable::parse($attendance->work_date)->format('Y-m-d');

        $clockInAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $workDate . ' ' . $request->input('clock_in_at')
        );

        $clockOutAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $workDate . ' ' . $request->input('clock_out_at')
        );

        $note = (string) $request->input('note', '');

        $breaks = $request->input('breaks', []);
        if (!is_array($breaks)) {
            $breaks = [];
        }

        DB::transaction(function () use ($attendance, $userId, $clockInAt, $clockOutAt, $note, $breaks, $workDate) {
            $correctionRequest = CorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'requested_by' => $userId,
                'status' => CorrectionRequest::STATUS_PENDING,
                'requested_clock_in_at' => $clockInAt,
                'requested_clock_out_at' => $clockOutAt,
                'requested_note' => $note,
            ]);

            foreach ($breaks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $start = $row['start'] ?? '';
                $end = $row['end'] ?? '';

                // 空行は無視
                if ($start === '' && $end === '') {
                    continue;
                }

                // FormRequestで片方だけ入力は弾いている前提
                if (!is_string($start) || $start === '' || !is_string($end) || $end === '') {
                    continue;
                }

                $breakStartAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate . ' ' . $start);
                $breakEndAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate . ' ' . $end);

                CorrectionRequestBreak::create([
                    'correction_request_id' => $correctionRequest->id,
                    'break_start_at' => $breakStartAt,
                    'break_end_at' => $breakEndAt,
                ]);
            }
        });

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
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

        // 通常モードは入力用に空1行を追加（Figma要件）
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
