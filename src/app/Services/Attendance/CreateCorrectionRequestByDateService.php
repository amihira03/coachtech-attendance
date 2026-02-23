<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateCorrectionRequestByDateService
{
    public function handle(int $userId, string $date, array $validated): int
    {
        return DB::transaction(function () use ($userId, $date, $validated): int {
            $attendance = Attendance::query()->firstOrCreate(
                [
                    'user_id' => $userId,
                    'work_date' => $date,
                ],
                [
                    'status' => Attendance::STATUS_OFF_DUTY,
                ]
            );

            $alreadyPending = CorrectionRequest::query()
                ->where('attendance_id', $attendance->id)
                ->where('requested_by', $userId)
                ->where('status', CorrectionRequest::STATUS_PENDING)
                ->exists();

            if ($alreadyPending) {
                return (int) $attendance->id;
            }

            $workDate = CarbonImmutable::parse($date)->format('Y-m-d');

            $clockInAt = CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $workDate . ' ' . (string) ($validated['clock_in_at'] ?? '')
            );

            $clockOutAt = CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $workDate . ' ' . (string) ($validated['clock_out_at'] ?? '')
            );

            $note = (string) ($validated['note'] ?? '');

            $correctionRequest = CorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'requested_by' => $userId,
                'status' => CorrectionRequest::STATUS_PENDING,
                'requested_clock_in_at' => $clockInAt,
                'requested_clock_out_at' => $clockOutAt,
                'requested_note' => $note,
            ]);

            $breaks = $validated['breaks'] ?? [];
            if (!is_array($breaks)) {
                $breaks = [];
            }

            foreach ($breaks as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $start = $row['start'] ?? '';
                $end = $row['end'] ?? '';

                if (!is_string($start) || !is_string($end)) {
                    continue;
                }

                if ($start === '' && $end === '') {
                    continue;
                }

                if ($start === '' || $end === '') {
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

            return (int) $attendance->id;
        });
    }
}
