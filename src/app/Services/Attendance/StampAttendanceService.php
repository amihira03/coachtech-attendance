<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class StampAttendanceService
{
    public function handle(int $userId, string $action, CarbonImmutable $now): void
    {
        $today = $now->toDateString();

        DB::transaction(function () use ($userId, $action, $now, $today) {
            $attendance = Attendance::query()
                ->where('user_id', $userId)
                ->where('work_date', $today)
                ->lockForUpdate()
                ->first();

            $currentStatus = $attendance?->status ?? Attendance::STATUS_OFF_DUTY;

            if ($action === 'clock_in') {
                if ($currentStatus !== Attendance::STATUS_OFF_DUTY) {
                    return;
                }

                $attendance = $attendance ?? new Attendance([
                    'user_id' => $userId,
                    'work_date' => $today,
                ]);

                if ($attendance->clock_in_at !== null) {
                    return;
                }

                $attendance->clock_in_at = $now;
                $attendance->status = Attendance::STATUS_WORKING;
                $attendance->save();

                return;
            }

            if ($action === 'break_start') {
                if ($currentStatus !== Attendance::STATUS_WORKING || $attendance === null) {
                    return;
                }

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $now,
                ]);

                $attendance->status = Attendance::STATUS_ON_BREAK;
                $attendance->save();

                return;
            }

            if ($action === 'break_end') {
                if ($currentStatus !== Attendance::STATUS_ON_BREAK || $attendance === null) {
                    return;
                }

                $break = BreakTime::query()
                    ->where('attendance_id', $attendance->id)
                    ->whereNull('break_end_at')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($break === null) {
                    return;
                }

                $break->break_end_at = $now;
                $break->save();

                $attendance->status = Attendance::STATUS_WORKING;
                $attendance->save();

                return;
            }

            if ($action === 'clock_out') {
                if ($currentStatus !== Attendance::STATUS_WORKING || $attendance === null) {
                    return;
                }

                if ($attendance->clock_out_at !== null) {
                    return;
                }

                $attendance->clock_out_at = $now;
                $attendance->status = Attendance::STATUS_FINISHED;
                $attendance->save();

                return;
            }
        });
    }
}
