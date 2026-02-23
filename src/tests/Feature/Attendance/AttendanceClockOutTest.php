<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanClockOut(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 18, 0, 0));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_WORKING,
            'clock_in_at' => '2026-02-21 09:00:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('value="clock_out"', false);

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'clock_out'])
            ->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_FINISHED,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('退勤済');
    }

    public function testClockOutTimeIsDisplayedOnAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 9, 0, 0));

        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'clock_in'])
            ->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2026, 2, 21, 18, 0, 0));

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'clock_out'])
            ->assertStatus(302);

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertOk()
            ->assertSee('02/21(土)')
            ->assertSee('18:00');
    }
}
