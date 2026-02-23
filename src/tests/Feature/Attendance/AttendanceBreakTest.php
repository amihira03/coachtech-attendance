<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkingAttendance(User $user): void
    {
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_WORKING,
            'clock_in_at' => '2026-02-21 09:00:00',
        ]);
    }

    public function testUserCanStartBreak(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 12, 0, 0));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('value="break_start"', false);

        $this->actingAs($user)
            ->post('/attendance', ['action' => 'break_start'])
            ->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_ON_BREAK,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('value="break_end"', false);
    }

    public function testUserCanStartBreakMultipleTimesInADay(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 12, 0, 0));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post('/attendance', ['action' => 'break_start'])->assertStatus(302);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_end'])->assertStatus(302);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('value="break_start"', false);
    }

    public function testUserCanEndBreak(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 12, 0, 0));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post('/attendance', ['action' => 'break_start'])->assertStatus(302);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_end'])->assertStatus(302);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-02-21',
            'status' => Attendance::STATUS_WORKING,
        ]);
    }

    public function testUserCanEndBreakMultipleTimesInADay(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 12, 0, 0));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post('/attendance', ['action' => 'break_start'])->assertStatus(302);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_end'])->assertStatus(302);
        $this->actingAs($user)->post('/attendance', ['action' => 'break_start'])->assertStatus(302);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('value="break_end"', false);
    }

    public function testBreakTimesAreDisplayedOnAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21, 12, 0, 0));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post('/attendance', ['action' => 'break_start'])->assertStatus(302);

        Carbon::setTestNow(Carbon::create(2026, 2, 21, 12, 30, 0));

        $this->actingAs($user)->post('/attendance', ['action' => 'break_end'])->assertStatus(302);

        $this->actingAs($user)
            ->get('/attendance/list')
            ->assertOk()
            ->assertSee('02/21(土)')
            ->assertSee('0:30');
    }
}
