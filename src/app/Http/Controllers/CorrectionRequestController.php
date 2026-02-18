<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreak;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Collection;

class CorrectionRequestController extends Controller
{
    /**
     * 申請一覧（一般ユーザー / 管理者 共通URL）
     * - 一般: requested_by = 自分 + tab(pending|approved)で絞り込み
     * - 管理: 全ユーザー + tab(pending|approved)で絞り込み（別view）
     */
    public function showList(Request $request): View
    {
        $user = auth()->user();

        // 管理者は別viewへ
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

    /**
     * 管理者：申請一覧
     * - 全ユーザーの申請を status で絞り込み
     */
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

            // 二重承認ガード
            if ($cr->status === CorrectionRequest::STATUS_APPROVED) {
                return;
            }

            $attendance = $cr->attendance;
            if (!$attendance instanceof Attendance) {
                return;
            }

            // work_date（"YYYY-MM-DD"）を基準に、H:i なら日時に組み立てる
            $workDate = is_string($attendance->work_date)
                ? $attendance->work_date
                : $attendance->work_date->format('Y-m-d');

            // 1) 勤怠へ反映
            $attendance->clock_in_at  = $this->toDateTimeOrKeep($workDate, $cr->requested_clock_in_at);
            $attendance->clock_out_at = $this->toDateTimeOrKeep($workDate, $cr->requested_clock_out_at);
            $attendance->note = $cr->requested_note;
            $attendance->save();

            // 2) 休憩を全削除 → 申請休憩で作り直し
            BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->delete();

            foreach ($cr->breaks as $reqBreak) {
                /** @var CorrectionRequestBreak $reqBreak */
                if ($reqBreak->break_start_at === null || $reqBreak->break_end_at === null) {
                    continue;
                }

                BreakTime::query()->create([
                    'attendance_id'   => $attendance->id,
                    'break_start_at'  => $this->toDateTimeOrKeep($workDate, $reqBreak->break_start_at),
                    'break_end_at'    => $this->toDateTimeOrKeep($workDate, $reqBreak->break_end_at),
                ]);
            }

            // 3) 申請を承認済みに更新
            $cr->status = CorrectionRequest::STATUS_APPROVED;
            $cr->approved_by = $adminId;
            $cr->approved_at = CarbonImmutable::now();
            $cr->save();
        });

        // 承認後は同じ詳細へ戻す（A案）
        return redirect()
            ->route('stamp_correction_request.approve.show', [
                'attendance_correct_request_id' => $attendance_correct_request_id,
            ])
            ->with('success', '申請を承認しました。');
    }

    /**
     * 値が "H:i" なら work_date と結合して datetime にする。
     * すでに datetime / Carbon 系ならそのまま返す。
     *
     * @param string $workDate 例: "2023-06-01"
     * @param mixed  $value    "09:00" / "2023-06-01 09:00:00" / Carbon など
     * @return mixed
     */
    private function toDateTimeOrKeep(string $workDate, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // Carbon/DateTime はそのまま
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // "09:00" 形式なら日付を付ける
        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return CarbonImmutable::parse($workDate . ' ' . $value);
        }

        // それ以外（"2023-06-01 09:00:00" 等）は parse して返す
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

    /**
     * @param Collection<int, CorrectionRequest> $requests
     * @return array<int, array<string, mixed>>
     */
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
