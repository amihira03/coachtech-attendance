<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminCanSeeAllUsersOnStaffList(): void
    {
        $admin = $this->createVerifiedAdmin();

        $user1 = User::factory()->create([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $user2 = User::factory()->create([
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $this->actingAs($admin)
            ->get('/admin/staff/list')
            ->assertOk()
            ->assertSee($user1->name)
            ->assertSee($user1->email)
            ->assertSee($user2->name)
            ->assertSee($user2->email);
    }

    public function testAdminCanSeeSelectedUserAttendanceList(): void
    {
        $admin = $this->createVerifiedAdmin();
        $user = $this->createVerifiedUser();

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-05',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-05 10:00:00',
            'clock_out_at' => '2026-02-05 19:00:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/staff/' . $user->id)
            ->assertOk()
            ->assertSee('02/02(月)')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('02/05(木)')
            ->assertSee('10:00')
            ->assertSee('19:00');
    }

    public function testAdminCanViewPreviousMonthOnStaffAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 10));

        $admin = $this->createVerifiedAdmin();
        $user  = $this->createVerifiedUser();

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-15',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-01-15 09:00:00',
            'clock_out_at' => '2026-01-15 18:00:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-01')
            ->assertOk()
            ->assertSee('2026/01')
            ->assertSee('01/15')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    public function testAdminCanViewNextMonthOnStaffAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 10));

        $admin = $this->createVerifiedAdmin();
        $user  = $this->createVerifiedUser();

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-15',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-03-15 09:00:00',
            'clock_out_at' => '2026-03-15 18:00:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-03')
            ->assertOk()
            ->assertSee('2026/03')
            ->assertSee('03/15')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    public function testAdminCanGoToAttendanceDetailFromStaffAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 10));

        $admin = $this->createVerifiedAdmin();
        $user  = $this->createVerifiedUser();

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/staff/' . $user->id . '?month=2026-02')
            ->assertOk()
            ->assertSee('/admin/attendance/' . $attendance->id, false);
    }
}
