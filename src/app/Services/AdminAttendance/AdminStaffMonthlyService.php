<?php

namespace App\Services\AdminAttendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffMonthlyService
{
    public function buildViewData(int $userId, string $month): array
    {
        $user = User::query()->findOrFail($userId);

        [$base, $start, $end] = $this->resolveMonthRange($month);

        $rows = $this->buildMonthlyRows($user->id, $start, $end);

        return [
            'user' => $user,
            'monthLabel' => $base->format('Y/m'),
            'prevMonth' => $base->subMonth()->format('Y-m'),
            'nextMonth' => $base->addMonth()->format('Y-m'),
            'rows' => $rows,
            'detailRouteName' => 'admin.attendance.show',
        ];
    }

    public function streamCsv(int $userId, string $month): StreamedResponse
    {
        $user = User::query()->findOrFail($userId);

        [$base, $start, $end] = $this->resolveMonthRange($month);
        $rows = $this->buildMonthlyRows($user->id, $start, $end);

        $fileMonth = $base->format('Y-m');
        $fileName = '勤怠_' . $user->name . '_' . $fileMonth . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    (string) $row['dateLabel'],
                    (string) $row['clockIn'],
                    (string) $row['clockOut'],
                    (string) $row['breakTotal'],
                    (string) $row['workTotal'],
                ]);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveMonthRange(string $month): array
    {
        $now = CarbonImmutable::now();

        $base = preg_match('/^\d{4}-\d{2}$/', $month) === 1
            ? CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()
            : $now->startOfMonth();

        $start = $base->startOfMonth();
        $end = $base->endOfMonth();

        return [$base, $start, $end];
    }

    private function buildMonthlyRows(int $userId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        [$attendanceByDate, $attendanceIds] = $this->indexAttendancesByDate($attendances);

        $breaksByAttendanceId = $this->loadBreaksByAttendanceIds($attendanceIds);

        $rows = [];
        $cursor = $start;

        while ($cursor->lte($end)) {
            $rows[] = $this->buildRow(
                $cursor,
                $attendanceByDate[$cursor->toDateString()] ?? null,
                $breaksByAttendanceId
            );

            $cursor = $cursor->addDay();
        }

        return $rows;
    }

    private function indexAttendancesByDate($attendances): array
    {
        $attendanceByDate = [];
        $attendanceIds = [];

        foreach ($attendances as $attendance) {
            $workDate = $attendance->work_date instanceof \DateTimeInterface
                ? CarbonImmutable::parse($attendance->work_date)->toDateString()
                : (string) $attendance->work_date;

            $attendanceByDate[$workDate] = $attendance;
            $attendanceIds[] = $attendance->id;
        }

        return [$attendanceByDate, $attendanceIds];
    }

    private function loadBreaksByAttendanceIds(array $attendanceIds): array
    {
        $breaksByAttendanceId = [];

        if (empty($attendanceIds)) {
            return $breaksByAttendanceId;
        }

        $breaks = BreakTime::query()
            ->whereIn('attendance_id', $attendanceIds)
            ->get();

        foreach ($breaks as $break) {
            $breaksByAttendanceId[$break->attendance_id][] = $break;
        }

        return $breaksByAttendanceId;
    }

    private function buildRow(
        CarbonImmutable $cursor,
        ?Attendance $attendance,
        array $breaksByAttendanceId
    ): array {
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

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

            $breakSeconds = $this->sumBreakSeconds($breaksByAttendanceId[$attendance->id] ?? []);
            $breakTotal = $breakSeconds > 0 ? $this->formatDuration($breakSeconds) : '';

            $workTotal = $this->calcWorkTotal($attendance, $breakSeconds);
        }

        return [
            'dateLabel' => $cursor->format('m/d') . '(' . $weekdays[$cursor->dayOfWeek] . ')',
            'clockIn' => $clockIn,
            'clockOut' => $clockOut,
            'breakTotal' => $breakTotal,
            'workTotal' => $workTotal,
            'attendanceId' => $attendanceId,
        ];
    }

    private function sumBreakSeconds(array $breaks): int
    {
        $breakSeconds = 0;

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

        return $breakSeconds;
    }

    private function calcWorkTotal(Attendance $attendance, int $breakSeconds): string
    {
        if ($attendance->clock_in_at === null || $attendance->clock_out_at === null) {
            return '';
        }

        $clockInAt = CarbonImmutable::parse($attendance->clock_in_at);
        $clockOutAt = CarbonImmutable::parse($attendance->clock_out_at);

        if ($clockOutAt->lt($clockInAt)) {
            return '';
        }

        $workSeconds = $clockInAt->diffInSeconds($clockOutAt) - $breakSeconds;
        if ($workSeconds < 0) {
            $workSeconds = 0;
        }

        return $this->formatDuration($workSeconds);
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
    }
}
