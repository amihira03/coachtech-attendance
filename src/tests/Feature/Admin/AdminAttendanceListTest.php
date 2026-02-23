<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminCanSeeAllUsersAttendancesForTheDay(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 0, 0));

        $user1 = $this->createVerifiedUser();
        $user2 = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
            'clock_out_at' => '2026-02-21 18:00:00',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 10:00:00',
            'clock_out_at' => '2026-02-21 19:00:00',
        ]);

        $admin = $this->createVerifiedAdmin();

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertOk()
            ->assertSee($user1->name)
            ->assertSee($user2->name);
    }

    public function testCurrentDateIsDisplayedOnAdminAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 0, 0));

        $admin = $this->createVerifiedAdmin();

        $this->actingAs($admin)
            ->get('/admin/attendance/list')
            ->assertOk()
            ->assertSee('2026/02/21');
    }

    public function testPreviousDayButtonShowsPreviousDate(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();

        $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-02-20')
            ->assertOk()
            ->assertSee('2026/02/20');
    }

    public function testNextDayButtonShowsNextDate(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();

        $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-02-22')
            ->assertOk()
            ->assertSee('2026/02/22');
    }
}
