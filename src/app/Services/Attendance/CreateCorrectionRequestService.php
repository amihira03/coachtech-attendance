<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateCorrectionRequestService
{
    public function handle(int $userId, int $attendanceId, array $validated): array
    {
        $attendance = Attendance::query()
            ->where('id', $attendanceId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $alreadyPending = CorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('requested_by', $userId)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($alreadyPending) {
            return ['created' => false, 'attendanceId' => $attendance->id];
        }

        $workDate = CarbonImmutable::parse($attendance->work_date)->format('Y-m-d');

        $clockInAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $workDate . ' ' . (string) ($validated['clock_in_at'] ?? '')
        );

        $clockOutAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $workDate . ' ' . (string) ($validated['clock_out_at'] ?? '')
        );

        $note = (string) ($validated['note'] ?? '');

        $breaks = $validated['breaks'] ?? [];
        if (!is_array($breaks)) {
            $breaks = [];
        }

        DB::transaction(function () use ($attendance, $userId, $clockInAt, $clockOutAt, $note, $breaks, $workDate) {
            $correctionRequest = CorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'requested_by' => $userId,
                'status' => CorrectionRequest::STATUS_PENDING,
                'requested_clock_in_at' => $clockInAt,
                'requested_clock_out_at' => $clockOutAt,
                'requested_note' => $note,
            ]);

            foreach ($breaks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $start = $row['start'] ?? '';
                $end = $row['end'] ?? '';

                if ($start === '' && $end === '') {
                    continue;
                }

                if (!is_string($start) || $start === '' || !is_string($end) || $end === '') {
                    continue;
                }

                $breakStartAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate . ' ' . $start);
                $breakEndAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate . ' ' . $end);

                CorrectionRequestBreak::create([
                    'correction_request_id' => $correctionRequest->id,
                    'break_start_at' => $breakStartAt,
                    'break_end_at' => $breakEndAt,
                ]);
            }
        });

        return ['created' => true, 'attendanceId' => $attendance->id];
    }
}
