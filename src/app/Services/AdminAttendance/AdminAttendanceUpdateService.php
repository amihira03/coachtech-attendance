<?php

namespace App\Services\AdminAttendance;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminAttendanceUpdateService
{
    public function handle(int $attendanceId, array $validated): Attendance
    {
        $attendance = Attendance::query()
            ->with(['breaks' => fn($q) => $q->orderBy('id')])
            ->findOrFail($attendanceId);

        $workDate = CarbonImmutable::parse($attendance->work_date)->format('Y-m-d');

        $clockInAt = $this->toDateTime($workDate, (string) ($validated['clock_in_at'] ?? ''));
        $clockOutAt = $this->toDateTime($workDate, (string) ($validated['clock_out_at'] ?? ''));

        $note = (string) ($validated['note'] ?? '');
        $breaksInput = $validated['breaks'] ?? [];

        DB::transaction(function () use ($attendance, $clockInAt, $clockOutAt, $note, $breaksInput, $workDate) {
            $this->updateAttendanceBase($attendance, $clockInAt, $clockOutAt, $note);

            $this->syncBreakTimes($attendance, $workDate, $breaksInput);

            $this->deletePendingRequests($attendance->id);
        });

        return $attendance->refresh();
    }

    private function updateAttendanceBase(
        Attendance $attendance,
        CarbonImmutable $clockInAt,
        CarbonImmutable $clockOutAt,
        string $note
    ): void {
        $attendance->update([
            'clock_in_at' => $clockInAt,
            'clock_out_at' => $clockOutAt,
            'note' => $note,
        ]);
    }

    private function syncBreakTimes(Attendance $attendance, string $workDate, mixed $breaksInput): void
    {
        if (!is_array($breaksInput)) {
            $breaksInput = [];
        }

        $keepExistingIds = $this->extractKeepExistingIds($breaksInput);

        $this->deleteRemovedBreaks($attendance->id, $keepExistingIds);

        $this->upsertBreaks($attendance->id, $workDate, $breaksInput);
    }

    private function extractKeepExistingIds(array $breaksInput): array
    {
        $keep = [];

        foreach ($breaksInput as $row) {
            if (!is_array($row)) {
                continue;
            }

            $breakId = $row['id'] ?? null;
            $start = $row['start'] ?? null;
            $end = $row['end'] ?? null;

            if (!is_numeric($breakId)) {
                continue;
            }

            $startEmpty = $start === null || $start === '';
            $endEmpty = $end === null || $end === '';

            if ($startEmpty && $endEmpty) {
                continue;
            }

            $keep[] = (int) $breakId;
        }

        return $keep;
    }

    private function deleteRemovedBreaks(int $attendanceId, array $keepExistingIds): void
    {
        BreakTime::query()
            ->where('attendance_id', $attendanceId)
            ->when(count($keepExistingIds) > 0, function ($q) use ($keepExistingIds) {
                $q->whereNotIn('id', $keepExistingIds);
            })
            ->delete();
    }

    private function upsertBreaks(int $attendanceId, string $workDate, array $breaksInput): void
    {
        foreach ($breaksInput as $row) {
            if (!is_array($row)) {
                continue;
            }

            $breakId = $row['id'] ?? null;
            $start = $row['start'] ?? null;
            $end = $row['end'] ?? null;

            $startEmpty = $start === null || $start === '';
            $endEmpty = $end === null || $end === '';

            if ($startEmpty && $endEmpty) {
                continue;
            }

            $startAt = $this->toDateTime($workDate, (string) $start);
            $endAt = $this->toDateTime($workDate, (string) $end);

            if (is_numeric($breakId)) {
                BreakTime::query()
                    ->where('id', (int) $breakId)
                    ->where('attendance_id', $attendanceId)
                    ->update([
                        'break_start_at' => $startAt,
                        'break_end_at' => $endAt,
                    ]);
                continue;
            }

            BreakTime::query()->create([
                'attendance_id' => $attendanceId,
                'break_start_at' => $startAt,
                'break_end_at' => $endAt,
            ]);
        }
    }

    private function deletePendingRequests(int $attendanceId): void
    {
        $pendingRequests = CorrectionRequest::query()
            ->where('attendance_id', $attendanceId)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->get();

        foreach ($pendingRequests as $pending) {
            $pending->breaks()->delete();
            $pending->delete();
        }
    }

    private function toDateTime(string $workDate, string $time): CarbonImmutable
    {
        $dt = CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate . ' ' . $time);

        if ($dt === false) {
            throw new RuntimeException('Invalid datetime: ' . $workDate . ' ' . $time);
        }

        return $dt;
    }
}
