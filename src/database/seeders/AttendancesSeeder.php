<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendancesSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->where('is_admin', false)
            ->orderBy('id')
            ->get();

        DB::transaction(function () use ($users): void {
            foreach ($users as $user) {
                $this->seedUserAttendances((int) $user->id);
            }
        });
    }

    private function seedUserAttendances(int $userId): void
    {
        $today = CarbonImmutable::today();

        $dates = [];
        for ($i = 1; $i <= 30; $i++) {
            $d = $today->subDays($i);

            if ($d->isWeekend()) {
                continue;
            }

            $dates[] = $d;

            if (count($dates) >= 20) {
                break;
            }
        }

        foreach ($dates as $workDate) {
            $this->createOneDay($userId, $workDate);
        }
    }

    private function createOneDay(int $userId, CarbonImmutable $workDate): void
    {
        $exists = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $workDate->toDateString())
            ->exists();

        if ($exists) {
            return;
        }

        $clockIn = $workDate->setTime(9, 0)->addMinutes(random_int(0, 60));

        $roll = random_int(1, 100);

        $status = Attendance::STATUS_FINISHED;
        $clockOut = $workDate->setTime(18, 0)->addMinutes(random_int(0, 60));

        if ($roll <= 8) {
            $status = Attendance::STATUS_WORKING;
            $clockOut = null;
        } elseif ($roll <= 10) {
            $status = Attendance::STATUS_ON_BREAK;
            $clockOut = null;
        }

        $attendance = Attendance::query()->create([
            'user_id' => $userId,
            'work_date' => $workDate->toDateString(),
            'clock_in_at' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out_at' => $clockOut?->format('Y-m-d H:i:s'),
            'status' => $status,
            'note' => null,
        ]);

        if ($clockOut === null) {
            return;
        }

        $remaining = 90;

        $breakCount = random_int(0, 2);

        for ($i = 0; $i < $breakCount; $i++) {
            if ($remaining < 15) {
                break;
            }

            $duration = random_int(15, min(60, $remaining));

            $this->createBreakWithDuration(
                (int) $attendance->id,
                $clockIn,
                $clockOut,
                $i,
                $duration
            );

            $remaining -= $duration;
        }
    }

    private function createBreakWithDuration(
        int $attendanceId,
        CarbonImmutable $clockIn,
        CarbonImmutable $clockOut,
        int $index,
        int $durationMinutes
    ): void {
        $startBase = $clockIn->setTime(12, 0)->addMinutes($index * 60);
        $start = $startBase->addMinutes(random_int(0, 30));

        $end = $start->addMinutes($durationMinutes);

        if ($end->greaterThan($clockOut)) {
            $end = $clockOut->subMinutes(5);
            $start = $end->subMinutes($durationMinutes);
        }

        BreakTime::query()->create([
            'attendance_id' => $attendanceId,
            'break_start_at' => $start->format('Y-m-d H:i:s'),
            'break_end_at' => $end->format('Y-m-d H:i:s'),
        ]);
    }
}
