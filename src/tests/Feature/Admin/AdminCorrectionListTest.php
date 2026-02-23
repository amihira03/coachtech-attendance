<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminCorrectionListTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminCanSeeAllPendingCorrectionRequests(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();
        $user1 = $this->createVerifiedUser();
        $user2 = $this->createVerifiedUser();

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-02-20',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-20 09:00:00',
            'clock_out_at' => '2026-02-20 18:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-02-20',
            'status' => Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-20 09:00:00',
            'clock_out_at' => '2026-02-20 18:00:00',
        ]);

        CorrectionRequest::create([
            'attendance_id' => $attendance1->id,
            'requested_by' => $user1->id,
            'status' => CorrectionRequest::STATUS_PENDING,
            'requested_note' => '修正1',
        ]);

        CorrectionRequest::create([
            'attendance_id' => $attendance2->id,
            'requested_by' => $user2->id,
            'status' => CorrectionRequest::STATUS_PENDING,
            'requested_note' => '修正2',
        ]);

        $this->actingAs($admin)
            ->get('/stamp_correction_request/list?tab=pending')
            ->assertOk()
            ->assertSee('修正1')
            ->assertSee('修正2');
    }

    public function testAdminCanSeeAllApprovedCorrectionRequests(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();
        $user  = $this->createVerifiedUser();

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-20',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-20 09:00:00',
            'clock_out_at' => '2026-02-20 18:00:00',
        ]);

        \App\Models\CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_by'  => $user->id,
            'status'        => \App\Models\CorrectionRequest::STATUS_APPROVED,
            'requested_note' => '承認済テスト',
            'approved_by'   => $admin->id,
            'approved_at'   => now(),
        ]);

        $this->actingAs($admin)
            ->get('/stamp_correction_request/list?tab=approved')
            ->assertOk()
            ->assertSee('承認済テスト');
    }

    public function testAdminCanSeeCorrectionRequestDetail(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();
        $user  = $this->createVerifiedUser();

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-20',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-20 09:00:00',
            'clock_out_at' => '2026-02-20 18:00:00',
        ]);

        $request = \App\Models\CorrectionRequest::create([
            'attendance_id'      => $attendance->id,
            'requested_by'       => $user->id,
            'status'             => \App\Models\CorrectionRequest::STATUS_PENDING,
            'requested_clock_in_at'  => '2026-02-20 09:30:00',
            'requested_clock_out_at' => '2026-02-20 18:30:00',
            'requested_note'     => '詳細テスト',
        ]);

        $this->actingAs($admin)
            ->get('/stamp_correction_request/approve/' . $request->id)
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee('09:30')
            ->assertSee('18:30')
            ->assertSee('詳細テスト');
    }

    public function testAdminCanApproveCorrectionRequestAndAttendanceIsUpdated(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 21));

        $admin = $this->createVerifiedAdmin();
        $user  = $this->createVerifiedUser();

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-20',
            'status' => \App\Models\Attendance::STATUS_FINISHED,
            'clock_in_at' => '2026-02-20 09:00:00',
            'clock_out_at' => '2026-02-20 18:00:00',
        ]);

        $request = \App\Models\CorrectionRequest::create([
            'attendance_id'           => $attendance->id,
            'requested_by'            => $user->id,
            'status'                  => \App\Models\CorrectionRequest::STATUS_PENDING,
            'requested_clock_in_at'   => '2026-02-20 09:30:00',
            'requested_clock_out_at'  => '2026-02-20 18:30:00',
            'requested_note'          => '承認処理テスト',
        ]);

        $this->actingAs($admin)
            ->post('/stamp_correction_request/approve/' . $request->id);

        $this->assertDatabaseHas('correction_requests', [
            'id' => $request->id,
            'status' => \App\Models\CorrectionRequest::STATUS_APPROVED,
            'approved_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in_at' => '2026-02-20 09:30:00',
            'clock_out_at' => '2026-02-20 18:30:00',
        ]);
    }
}
