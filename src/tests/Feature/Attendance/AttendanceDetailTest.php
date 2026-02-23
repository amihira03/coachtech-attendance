<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function testUserNameIsDisplayedOnDetail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
            'clock_out_at' => '2026-02-21 18:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance/detail/' . $attendance->id)
            ->assertOk()
            ->assertSee($user->name);
    }

    public function testWorkDateIsDisplayedOnDetail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
            'clock_out_at' => '2026-02-21 18:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance/detail/' . $attendance->id)
            ->assertOk()
            ->assertSee('2026/02/21(土)');
    }

    public function testClockInOutTimeIsDisplayedOnDetail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:01:00',
            'clock_out_at' => '2026-02-02 18:07:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance/detail/' . $attendance->id)
            ->assertOk()
            ->assertSee('09:01')
            ->assertSee('18:07');
    }

    public function testBreakTimeIsDisplayedOnDetail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:01:00',
            'clock_out_at' => '2026-02-02 18:07:00',
        ]);

        \App\Models\BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-02-02 12:00:00',
            'break_end_at' => '2026-02-02 12:30:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance/detail/' . $attendance->id)
            ->assertOk()
            ->assertSee('12:00')
            ->assertSee('12:30');
    }
}
