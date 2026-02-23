<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function testClockInAfterClockOutShowsValidationError(): void
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

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in_at' => '18:00',
            'clock_out_at' => '09:00',
            'note' => 'テスト備考',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'clock_in_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function testBreakStartAfterClockOutShowsValidationError(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'start' => '19:00',
                    'end' => '19:30',
                ],
            ],
            'note' => 'テスト備考',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です',
        ]);
    }

    public function testBreakEndAfterClockOutShowsValidationError(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                [
                    'start' => '17:30',
                    'end' => '18:30',
                ],
            ],
            'note' => 'テスト備考',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function testNoteRequiredShowsValidationError(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'note' => '',
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    public function testAdminCanSeeCorrectionRequestOnList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $this->actingAs($user)
            ->post('/attendance/detail/' . $attendance->id, [
                'clock_in_at' => '09:10',
                'clock_out_at' => '18:05',
                'note' => '修正申請テスト',
            ])
            ->assertStatus(302);

        $admin = $this->createVerifiedAdmin();

        $this->actingAs($admin)
            ->get('/stamp_correction_request/list')
            ->assertOk()
            ->assertSee('修正申請テスト');
    }

    public function testPendingTabShowsAllMyRequests(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance1 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-03',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-03 09:00:00',
            'clock_out_at' => '2026-02-03 18:00:00',
        ]);

        $this->actingAs($user)->post('/attendance/detail/' . $attendance1->id, [
            'clock_in_at' => '09:10',
            'clock_out_at' => '18:05',
            'note' => '申請1',
        ]);

        $this->actingAs($user)->post('/attendance/detail/' . $attendance2->id, [
            'clock_in_at' => '09:15',
            'clock_out_at' => '18:10',
            'note' => '申請2',
        ]);

        $this->actingAs($user)
            ->get('/stamp_correction_request/list?tab=pending')
            ->assertOk()
            ->assertSee('申請1')
            ->assertSee('申請2');
    }

    public function testApprovedTabShowsApprovedRequests(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in_at' => '09:10',
            'clock_out_at' => '18:05',
            'note' => '承認テスト',
        ]);

        $request = \App\Models\CorrectionRequest::first();

        $admin = $this->createVerifiedAdmin();

        $this->actingAs($admin)
            ->post('/stamp_correction_request/approve/' . $request->id);

        $this->actingAs($user)
            ->get('/stamp_correction_request/list?tab=approved')
            ->assertOk()
            ->assertSee('承認テスト');
    }

    public function testRequestDetailLinkRedirectsToAttendanceDetail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2, 10, 0, 0));

        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-02',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-02 09:00:00',
            'clock_out_at' => '2026-02-02 18:00:00',
        ]);

        $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in_at' => '09:10',
            'clock_out_at' => '18:05',
            'note' => '詳細遷移テスト',
        ]);

        $request = \App\Models\CorrectionRequest::first();

        $this->actingAs($user)
            ->get('/stamp_correction_request/list?tab=pending')
            ->assertOk()
            ->assertSee('詳細');

        $this->actingAs($user)
            ->get('/attendance/detail/' . $request->attendance_id)
            ->assertOk()
            ->assertSee('勤怠詳細');
    }
}
