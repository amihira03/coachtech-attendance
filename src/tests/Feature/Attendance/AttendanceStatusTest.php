<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function testStatusOffDutyIsDisplayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 35, 0));

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    public function testStatusWorkingIsDisplayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 35, 0));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_WORKING,
            'clock_in_at' => '2026-02-21 09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    public function testStatusOnBreakIsDisplayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 35, 0));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_ON_BREAK,
            'clock_in_at' => '2026-02-21 09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    public function testStatusFinishedIsDisplayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 35, 0));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }
}
