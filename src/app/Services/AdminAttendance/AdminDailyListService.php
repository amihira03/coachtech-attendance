<?php

namespace App\Services\AdminAttendance;

use App\Models\Attendance;
use Carbon\CarbonImmutable;

class AdminDailyListService
{
    public function buildRows(CarbonImmutable $displayDate): array
    {
        $attendances = Attendance::query()
            ->with([
                'user',
                'breaks' => function ($q) {
                    $q->whereNotNull('break_end_at')
                        ->select(['id', 'attendance_id', 'break_start_at', 'break_end_at']);
                },
            ])
            ->whereDate('work_date', $displayDate->toDateString())
            ->orderBy('user_id')
            ->get();

        $rows = [];

        foreach ($attendances as $attendance) {
            $clockIn = $attendance->clock_in_at ? $attendance->clock_in_at->format('H:i') : '';
            $clockOut = $attendance->clock_out_at ? $attendance->clock_out_at->format('H:i') : '';

            $breakMinutes = $this->sumBreakMinutes($attendance);
            $breakTotal = $breakMinutes > 0 ? $this->minutesToHm($breakMinutes) : '';

            $workTotal = $this->calcWorkTotal($attendance, $breakMinutes);

            $rows[] = [
                'attendance_id' => $attendance->id,
                'name' => $attendance->user?->name ?? '',
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_total' => $breakTotal,
                'work_total' => $workTotal,
            ];
        }

        return $rows;
    }

    private function sumBreakMinutes(Attendance $attendance): int
    {
        $breakMinutes = 0;

        foreach ($attendance->breaks as $break) {
            if ($break->break_start_at && $break->break_end_at) {
                $breakMinutes += $break->break_end_at->diffInMinutes($break->break_start_at);
            }
        }

        return $breakMinutes;
    }

    private function calcWorkTotal(Attendance $attendance, int $breakMinutes): string
    {
        if (!$attendance->clock_in_at || !$attendance->clock_out_at) {
            return '';
        }

        $workMinutes = $attendance->clock_out_at->diffInMinutes($attendance->clock_in_at) - $breakMinutes;

        if ($workMinutes < 0) {
            $workMinutes = 0;
        }

        return $this->minutesToHm($workMinutes);
    }

    private function minutesToHm(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours . ':' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT);
    }
}
