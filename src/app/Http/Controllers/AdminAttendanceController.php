<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceRequest;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AdminAttendance\AdminDailyListService;
use App\Services\AdminAttendance\AdminAttendanceUpdateService;
use App\Services\AdminAttendance\AdminStaffMonthlyService;

class AdminAttendanceController extends Controller
{
    public function showList(Request $request, AdminDailyListService $service): View
    {
        $date = $request->query('date');

        $displayDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) === 1
            ? CarbonImmutable::createFromFormat('Y-m-d', (string) $date)
            : CarbonImmutable::today();

        $prevDate = $displayDate->subDay();
        $nextDate = $displayDate->addDay();

        $rows = $service->buildRows($displayDate);

        return view('admin.attendance.list', compact('displayDate', 'prevDate', 'nextDate', 'rows'));
    }

    public function show(int $id): View
    {
        $attendance = Attendance::query()
            ->with([
                'user',
                'breaks' => fn($q) => $q->orderBy('id'),
            ])
            ->findOrFail($id);

        $pending = CorrectionRequest::query()
            ->with(['breaks' => fn($q) => $q->orderBy('id')])
            ->where('attendance_id', $attendance->id)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->latest('id')
            ->first();

        $displayClockIn = $this->toTimeString($pending?->requested_clock_in_at ?? $attendance->clock_in_at);
        $displayClockOut = $this->toTimeString($pending?->requested_clock_out_at ?? $attendance->clock_out_at);
        $displayNote = (string) ($pending?->requested_note ?? $attendance->note ?? '');

        $baseBreaks = $attendance->breaks ?? collect();
        $reqBreaks = $pending?->breaks ?? collect();

        $displayBreaks = [];

        if ($baseBreaks->count() > 0) {
            foreach ($baseBreaks->values() as $i => $base) {
                $req = $reqBreaks->values()->get($i);

                $displayBreaks[] = [
                    'id' => $base->id,
                    'start' => $this->toTimeString($req?->break_start_at ?? $base->break_start_at),
                    'end' => $this->toTimeString($req?->break_end_at ?? $base->break_end_at),
                ];
            }
        } else {
            foreach ($reqBreaks->values() as $req) {
                $displayBreaks[] = [
                    'id' => null,
                    'start' => $this->toTimeString($req->break_start_at ?? null),
                    'end' => $this->toTimeString($req->break_end_at ?? null),
                ];
            }
        }

        return view('admin.attendance.show', [
            'attendance' => $attendance,
            'displayClockIn' => $displayClockIn,
            'displayClockOut' => $displayClockOut,
            'displayNote' => $displayNote,
            'displayBreaks' => $displayBreaks,
            'hasPendingRequest' => $pending !== null,
        ]);
    }


    public function update(
        AdminAttendanceRequest $request,
        int $id,
        AdminAttendanceUpdateService $service
    ): RedirectResponse {
        $attendance = $service->handle($id, $request->validated());

        return redirect()->route('admin.attendance.list', [
            'date' => CarbonImmutable::parse($attendance->work_date)->toDateString(),
        ]);
    }

    private function toTimeString($value): string
    {
        if ($value === null) {
            return '';
        }

        $dt = $value instanceof \DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value);

        return $dt->format('H:i');
    }

    public function showStaffList(Request $request, int $id, AdminStaffMonthlyService $service): View
    {
        $month = (string) $request->query('month', '');

        $data = $service->buildViewData($id, $month);

        return view('admin.attendance.staff', $data);
    }

    public function exportStaffCsv(Request $request, int $id, AdminStaffMonthlyService $service): StreamedResponse
    {
        $month = (string) $request->query('month', '');

        return $service->streamCsv($id, $month);
    }
}
