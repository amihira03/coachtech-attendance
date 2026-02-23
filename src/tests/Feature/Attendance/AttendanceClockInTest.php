<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanClockIn(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 35, 0));

        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('出勤');

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'clock_in_at' => '2026-02-21 10:35:00',
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('出勤中');
    }

    public function testClockInButtonIsNotShownWhenFinished(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 18, 00, 0));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-21 09:00:00',
            'clock_out_at' => '2026-02-21 18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('name="action" value="clock_in"', false);
    }

    public function testClockInTimeIsDisplayedOnAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 10, 35, 0));

        $user = $this->createVerifiedUser();

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'clock_in'])
            ->assertStatus(302);

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertOk()
            ->assertSee('02/21(土)')
            ->assertSee('10:35');
    }
}
