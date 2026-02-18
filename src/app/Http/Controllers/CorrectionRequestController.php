<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Collection;

class CorrectionRequestController extends Controller
{
    public function showList(Request $request): View
    {
        $user = auth()->user();

        if ($user !== null && (bool) ($user->is_admin ?? false)) {
            return $this->showAdminList($request);
        }

        $tab = $this->normalizeTab((string) $request->query('tab', 'pending'));
        $status = $this->statusFromTab($tab);

        $requests = CorrectionRequest::query()
            ->with([
                'attendance:id,work_date',
                'requestedBy:id,name',
            ])
            ->where('requested_by', auth()->id())
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();

        return view('corrections.list', [
            'tab' => $tab,
            'rows' => $this->buildRows($requests),
        ]);
    }

    private function showAdminList(Request $request): View
    {
        $tab = $this->normalizeTab((string) $request->query('tab', 'pending'));
        $status = $this->statusFromTab($tab);

        $requests = CorrectionRequest::query()
            ->with([
                'attendance:id,work_date',
                'requestedBy:id,name',
            ])
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.corrections.list', [
            'tab' => $tab,
            'rows' => $this->buildRows($requests),
        ]);
    }

    public function show(int $attendance_correct_request_id): View
    {
        $correctionRequest = CorrectionRequest::query()
            ->with([
                'attendance:id,user_id,work_date,clock_in_at,clock_out_at,note',
                'requestedBy:id,name',
                'breaks:id,correction_request_id,break_start_at,break_end_at',
            ])
            ->findOrFail($attendance_correct_request_id);

        return view('admin.corrections.approve', [
            'correctionRequest' => $correctionRequest,
        ]);
    }

    public function confirm(Request $request, int $attendance_correct_request_id): RedirectResponse
    {
        $adminId = auth()->id();

        DB::transaction(function () use ($attendance_correct_request_id, $adminId) {
            /** @var CorrectionRequest $cr */
            $cr = CorrectionRequest::query()
                ->with([
                    'attendance:id,work_date',
                    'breaks:id,correction_request_id,break_start_at,break_end_at',
                ])
                ->lockForUpdate()
                ->findOrFail($attendance_correct_request_id);

            if ($cr->status === CorrectionRequest::STATUS_APPROVED) {
                return;
            }

            $attendance = $cr->attendance;
            if (!$attendance instanceof Attendance) {
                return;
            }

            $workDate = is_string($attendance->work_date)
                ? $attendance->work_date
                : $attendance->work_date->format('Y-m-d');

            $attendance->clock_in_at  = $this->toDateTimeOrKeep($workDate, $cr->requested_clock_in_at);
            $attendance->clock_out_at = $this->toDateTimeOrKeep($workDate, $cr->requested_clock_out_at);
            $attendance->note = $cr->requested_note;
            $attendance->save();

            BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->delete();

            foreach ($cr->breaks as $reqBreak) {
                if ($reqBreak->break_start_at === null || $reqBreak->break_end_at === null) {
                    continue;
                }

                BreakTime::query()->create([
                    'attendance_id'   => $attendance->id,
                    'break_start_at'  => $this->toDateTimeOrKeep($workDate, $reqBreak->break_start_at),
                    'break_end_at'    => $this->toDateTimeOrKeep($workDate, $reqBreak->break_end_at),
                ]);
            }

            $cr->status = CorrectionRequest::STATUS_APPROVED;
            $cr->approved_by = $adminId;
            $cr->approved_at = CarbonImmutable::now();
            $cr->save();
        });

        return redirect()
            ->route('stamp_correction_request.approve.show', [
                'attendance_correct_request_id' => $attendance_correct_request_id,
            ])
            ->with('success', '申請を承認しました。');
    }

    private function toDateTimeOrKeep(string $workDate, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return CarbonImmutable::parse($workDate . ' ' . $value);
        }

        return CarbonImmutable::parse($value);
    }

    private function normalizeTab(string $tab): string
    {
        return in_array($tab, ['pending', 'approved'], true) ? $tab : 'pending';
    }

    private function statusFromTab(string $tab): int
    {
        return $tab === 'approved'
            ? CorrectionRequest::STATUS_APPROVED
            : CorrectionRequest::STATUS_PENDING;
    }

    private function buildRows(Collection $requests): array
    {
        return $requests->map(function (CorrectionRequest $cr) {
            $workDate = $cr->attendance?->work_date;

            if ($workDate === null) {
                $workDateLabel = '—';
            } elseif (is_string($workDate)) {
                $workDateLabel = date('Y/m/d', strtotime($workDate));
            } else {
                $workDateLabel = $workDate->format('Y/m/d');
            }

            $requestedAtLabel = $cr->created_at?->format('Y/m/d') ?? '—';

            return [
                'statusLabel' => $cr->status === CorrectionRequest::STATUS_APPROVED ? '承認済み' : '承認待ち',
                'userName' => $cr->requestedBy?->name ?? '',
                'workDateLabel' => $workDateLabel,
                'requestedNote' => (string) ($cr->requested_note ?? ''),
                'requestedAtLabel' => $requestedAtLabel,
                'attendanceId' => $cr->attendance_id,
                'correctionRequestId' => $cr->id,
            ];
        })->values()->all();
    }
}
