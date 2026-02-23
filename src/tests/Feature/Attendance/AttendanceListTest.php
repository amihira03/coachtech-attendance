<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function testAllMyAttendancesAreDisplayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-05',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-05 09:00:00',
            'clock_out_at' => '2026-02-05 18:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertOk()
            ->assertSee('02/02')
            ->assertSee('02/05');
    }

    public function testCurrentMonthIsDisplayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertOk()
            ->assertSee('2026/02');
    }

    public function testPreviousMonthIsDisplayedWhenPrevMonthButtonIsClicked(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->get('/attendance/list?month=2026-01')
            ->assertOk()
            ->assertSee('2026/01');
    }

    public function testNextMonthIsDisplayedWhenNextMonthButtonIsClicked(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->get('/attendance/list?month=2026-03')
            ->assertOk()
            ->assertSee('2026/03');
    }

    public function testDetailLinkNavigatesToAttendanceDetail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertOk()
            ->assertSee('/attendance/detail/' . $attendance->id, false);

        $this->actingAs($user)
            ->get('/attendance/detail/' . $attendance->id)
            ->assertOk();
    }
}
