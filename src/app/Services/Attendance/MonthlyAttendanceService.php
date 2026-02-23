<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\CarbonImmutable;

class MonthlyAttendanceService
{
    public function build(int $userId, string $monthQuery, CarbonImmutable $now): array
    {
        $base = preg_match('/^\d{4}-\d{2}$/', $monthQuery) === 1
            ? CarbonImmutable::createFromFormat('Y-m', $monthQuery)->startOfMonth()
            : $now->startOfMonth();

        $start = $base->startOfMonth();
        $end = $base->endOfMonth();

        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $attendanceByDate = [];
        $attendanceIds = [];

        foreach ($attendances as $attendance) {
            $workDate = $attendance->work_date instanceof \DateTimeInterface
                ? CarbonImmutable::parse($attendance->work_date)->toDateString()
                : (string) $attendance->work_date;

            $attendanceByDate[$workDate] = $attendance;
            $attendanceIds[] = $attendance->id;
        }

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
                    $breakTotal = self::formatDuration($breakSeconds);
                }

                if ($attendance->clock_in_at !== null && $attendance->clock_out_at !== null) {
                    $clockInAt = CarbonImmutable::parse($attendance->clock_in_at);
                    $clockOutAt = CarbonImmutable::parse($attendance->clock_out_at);

                    if ($clockOutAt->gte($clockInAt)) {
                        $workSeconds = $clockInAt->diffInSeconds($clockOutAt) - $breakSeconds;

                        if ($workSeconds < 0) {
                            $workSeconds = 0;
                        }

                        $workTotal = self::formatDuration($workSeconds);
                    }
                }
            }

            $rows[] = [
                'dateLabel' => $cursor->format('m/d') . '(' . ($weekdays[$cursor->dayOfWeek] ?? '') . ')',
                'clockIn' => $clockIn,
                'clockOut' => $clockOut,
                'breakTotal' => $breakTotal,
                'workTotal' => $workTotal,
                'attendanceId' => $attendanceId,
                'workDate' => $date,
            ];

            $cursor = $cursor->addDay();
        }

        return [
            'monthLabel' => $base->format('Y/m'),
            'prevMonth' => $base->subMonth()->format('Y-m'),
            'nextMonth' => $base->addMonth()->format('Y-m'),
            'rows' => $rows,
        ];
    }

    private static function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
    }
}
