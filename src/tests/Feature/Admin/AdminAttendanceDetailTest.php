<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminAttendanceDetailShowsSelectedAttendance(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 0, 0));

        $admin = $this->createVerifiedAdmin();
        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:01:00',
            'clock_out_at' => '2026-02-21 18:07:00',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/' . $attendance->id)
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee('09:01')
            ->assertSee('18:07');
    }

    public function testAdminValidationFailsWhenClockInIsAfterClockOut(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();
        $user = $this->createVerifiedUser();

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
            'clock_out_at' => '2026-02-21 18:00:00',
        ]);

        $this->actingAs($admin)
            ->post('/admin/attendance/' . $attendance->id, [
                'clock_in_at' => '19:00',
                'clock_out_at' => '18:00',
                'note' => 'テスト',
            ])
            ->assertSessionHasErrors([
                'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
            ]);
    }

    public function testAdminValidationFailsWhenBreakStartIsAfterClockOut(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();
        $user = $this->createVerifiedUser();

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
            'clock_out_at' => '2026-02-21 18:00:00',
        ]);

        $this->actingAs($admin)
            ->post('/admin/attendance/' . $attendance->id, [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'start' => '19:00',
                        'end' => '19:30',
                    ],
                ],
                'note' => 'テスト',
            ])
            ->assertSessionHasErrors([
                'breaks.0.start' => '休憩時間が不適切な値です',
            ]);
    }

    public function testAdminValidationFailsWhenBreakEndIsAfterClockOut(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();
        $user = $this->createVerifiedUser();

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
            'clock_out_at' => '2026-02-21 18:00:00',
        ]);

        $this->actingAs($admin)
            ->post('/admin/attendance/' . $attendance->id, [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    [
                        'start' => '12:00',
                        'end'   => '19:00',
                    ],
                ],
                'note' => 'テスト',
            ])
            ->assertSessionHasErrors([
                'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
            ]);
    }

    public function testAdminValidationFailsWhenNoteIsEmpty(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();
        $user = $this->createVerifiedUser();

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
            'clock_out_at' => '2026-02-21 18:00:00',
        ]);

        $this->actingAs($admin)
            ->post('/admin/attendance/' . $attendance->id, [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'note' => '',
            ])
            ->assertSessionHasErrors([
                'note' => '備考を記入してください',
            ]);
    }
}
