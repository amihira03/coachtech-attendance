<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CorrectionRequestsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('is_admin', true)->first();
        $user = User::query()->where('is_admin', false)->first();

        if ($admin === null || $user === null) {
            return;
        }

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereNotNull('clock_out_at')
            ->orderBy('work_date', 'desc')
            ->take(2)
            ->get();

        if ($attendances->count() < 2) {
            return;
        }

        DB::transaction(function () use ($admin, $user, $attendances): void {
            $this->createPendingRequest((int) $user->id, (int) $attendances[0]->id);
            $this->createApprovedRequest((int) $admin->id, (int) $user->id, (int) $attendances[1]->id);
        });
    }

    private function createPendingRequest(int $requestedBy, int $attendanceId): void
    {
        $exists = CorrectionRequest::query()
            ->where('attendance_id', $attendanceId)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($exists) {
            return;
        }

        $req = CorrectionRequest::query()->create([
            'attendance_id' => $attendanceId,
            'requested_by' => $requestedBy,
            'status' => CorrectionRequest::STATUS_PENDING,
            'requested_clock_in_at' => null,
            'requested_clock_out_at' => null,
            'requested_note' => '電車遅延のため出勤時刻を修正したいです。',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->createRequestBreaks((int) $req->id);
    }

    private function createApprovedRequest(int $approvedBy, int $requestedBy, int $attendanceId): void
    {
        $exists = CorrectionRequest::query()
            ->where('attendance_id', $attendanceId)
            ->where('status', CorrectionRequest::STATUS_APPROVED)
            ->exists();

        if ($exists) {
            return;
        }

        $approvedAt = CarbonImmutable::now();

        $req = CorrectionRequest::query()->create([
            'attendance_id' => $attendanceId,
            'requested_by' => $requestedBy,
            'status' => CorrectionRequest::STATUS_APPROVED,
            'requested_clock_in_at' => null,
            'requested_clock_out_at' => null,
            'requested_note' => '早退したので退勤時刻を修正したいです。',
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt->format('Y-m-d H:i:s'),
        ]);

        $this->createRequestBreaks((int) $req->id);
    }

    private function createRequestBreaks(int $correctionRequestId): void
    {
        $count = random_int(1, 2);

        $base = CarbonImmutable::today()->setTime(12, 0);

        for ($i = 0; $i < $count; $i++) {
            $start = $base->addMinutes($i * 60);
            $end = $start->addMinutes(45);

            CorrectionRequestBreak::query()->create([
                'correction_request_id' => $correctionRequestId,
                'break_start_at' => $start->format('Y-m-d H:i:s'),
                'break_end_at' => $end->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
