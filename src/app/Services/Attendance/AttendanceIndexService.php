<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use Carbon\CarbonImmutable;

class AttendanceIndexService
{
    public function build(int $userId, CarbonImmutable $now): array
    {
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

        return [
            'now' => $now,
            'attendance' => $attendance,
            'status' => $status,
            'statusText' => $statusText,

            'isFinished' => $status === Attendance::STATUS_FINISHED,

            'showClockIn' => $status === Attendance::STATUS_OFF_DUTY,
            'showBreakStart' => $status === Attendance::STATUS_WORKING,
            'showBreakEnd' => $status === Attendance::STATUS_ON_BREAK,
            'showClockOut' => $status === Attendance::STATUS_WORKING,
        ];
    }
}
