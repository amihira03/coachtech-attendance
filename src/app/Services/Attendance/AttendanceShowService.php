<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\CarbonImmutable;

class AttendanceShowService
{
    public function build(int $userId, int $attendanceId, string $userName): array
    {
        $attendance = Attendance::query()
            ->where('id', $attendanceId)
            ->where('user_id', $userId)
            ->with(['breaks'])
            ->firstOrFail();

        $pending = CorrectionRequest::query()
            ->where('attendance_id', $attendance->id)
            ->where('requested_by', $userId)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->with(['breaks'])
            ->latest('id')
            ->first();

        $isPending = $pending !== null;

        $display = $isPending
            ? [
                'clockIn' => $this->formatTime($pending->requested_clock_in_at),
                'clockOut' => $this->formatTime($pending->requested_clock_out_at),
                'note' => (string) $pending->requested_note,
                'breakRows' => $this->mapCorrectionRequestBreakRows($pending),
            ]
            : [
                'clockIn' => $this->formatTime($attendance->clock_in_at),
                'clockOut' => $this->formatTime($attendance->clock_out_at),
                'note' => (string) ($attendance->note ?? ''),
                'breakRows' => $this->mapAttendanceBreakRows($attendance),
            ];

        return [
            'attendance' => $attendance,
            'attendanceId' => $attendance->id,
            'userName' => $userName,
            'displayDateLabel' => $this->formatDateLabel($attendance->work_date),

            'isPending' => $isPending,
            'pendingRequest' => $pending,
            'display' => $display,
        ];
    }

    private function formatTime(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return CarbonImmutable::parse($value)->format('H:i');
    }

    private function formatDateLabel(mixed $workDate): string
    {
        $date = CarbonImmutable::parse($workDate);

        return $date->format('Y/m/d') . '(' . $this->weekdayJa($date->dayOfWeek) . ')';
    }

    private function weekdayJa(int $dayOfWeek): string
    {
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        return $weekdays[$dayOfWeek] ?? '';
    }

    private function mapAttendanceBreakRows(Attendance $attendance): array
    {
        $rows = [];

        foreach ($attendance->breaks as $break) {
            $rows[] = [
                'start' => $this->formatTime($break->break_start_at),
                'end' => $this->formatTime($break->break_end_at),
            ];
        }

        $rows[] = ['start' => '', 'end' => ''];

        return $rows;
    }

    private function mapCorrectionRequestBreakRows(CorrectionRequest $correctionRequest): array
    {
        $rows = [];

        foreach ($correctionRequest->breaks as $break) {
            $rows[] = [
                'start' => $this->formatTime($break->break_start_at),
                'end' => $this->formatTime($break->break_end_at),
            ];
        }

        return $rows;
    }
}
