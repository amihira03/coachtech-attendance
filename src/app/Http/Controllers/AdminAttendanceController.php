<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceRequest;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    public function showList(Request $request): View
    {
        // 1) 表示日を決める（?date=YYYY-MM-DD があればそれ／なければ今日）
        $date = $request->query('date');

        $displayDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) === 1
            ? CarbonImmutable::createFromFormat('Y-m-d', (string) $date)
            : CarbonImmutable::today();

        $prevDate = $displayDate->subDay();
        $nextDate = $displayDate->addDay();

        // 2) 当日の勤怠を取得（その日に勤怠レコードがある人のみ）
        //    ※ userは名前表示に必要
        $attendances = Attendance::query()
            ->with([
                'user',
                'breaks' => function ($q) {
                    $q->whereNotNull('break_end_at')
                        ->select(['id', 'attendance_id', 'break_start_at', 'break_end_at']);
                },
            ])
            ->whereDate('work_date', $displayDate->toDateString())
            ->orderBy('user_id')
            ->get();

        // 3) 表示用の rows を作る
        $minutesToHm = static function (int $minutes): string {
            $hours = intdiv($minutes, 60);
            $mins = $minutes % 60;
            return $hours . ':' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT);
        };

        $rows = [];

        foreach ($attendances as $attendance) {
            $clockIn = $attendance->clock_in_at ? $attendance->clock_in_at->format('H:i') : '';
            $clockOut = $attendance->clock_out_at ? $attendance->clock_out_at->format('H:i') : '';

            $breakMinutes = 0;
            foreach ($attendance->breaks as $break) {
                if ($break->break_start_at && $break->break_end_at) {
                    $breakMinutes += $break->break_end_at->diffInMinutes($break->break_start_at);
                }
            }

            $breakTotal = $breakMinutes > 0 ? $minutesToHm($breakMinutes) : '';

            $workTotal = '';
            if ($attendance->clock_in_at && $attendance->clock_out_at) {
                $workMinutes = $attendance->clock_out_at->diffInMinutes($attendance->clock_in_at) - $breakMinutes;
                if ($workMinutes < 0) {
                    $workMinutes = 0;
                }
                $workTotal = $minutesToHm($workMinutes);
            }

            $rows[] = [
                'attendance_id' => $attendance->id,
                'name' => $attendance->user?->name ?? '',
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_total' => $breakTotal,
                'work_total' => $workTotal,
            ];
        }

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

        // ★ここが変更：申請があれば申請休憩をそのまま、なければ本体休憩
        $sourceBreaks = $pending
            ? ($pending->breaks ?? collect())
            : ($attendance->breaks ?? collect());

        $displayBreaks = $sourceBreaks->values()->map(function ($b) {
            return [
                // 表示用なので id は任意（フォームで使うなら別途）
                'id' => $b->id ?? null,
                'start' => $this->toTimeString($b->break_start_at ?? null),
                'end' => $this->toTimeString($b->break_end_at ?? null),
            ];
        })->all();

        return view('admin.attendance.show', [
            'attendance' => $attendance,
            'displayClockIn' => $displayClockIn,
            'displayClockOut' => $displayClockOut,
            'displayNote' => $displayNote,
            'displayBreaks' => $displayBreaks,
            'hasPendingRequest' => $pending !== null,
        ]);
    }


    public function update(AdminAttendanceRequest $request, int $id): RedirectResponse
    {
        $attendance = Attendance::query()
            ->with(['breaks' => fn($q) => $q->orderBy('id')])
            ->findOrFail($id);

        $workDate = CarbonImmutable::parse($attendance->work_date)->format('Y-m-d');

        $clockInAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $workDate . ' ' . $request->input('clock_in_at')
        );

        $clockOutAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $workDate . ' ' . $request->input('clock_out_at')
        );

        DB::transaction(function () use ($request, $attendance, $clockInAt, $clockOutAt, $workDate) {

            // 1) attendance本体を更新
            $attendance->update([
                'clock_in_at' => $clockInAt,
                'clock_out_at' => $clockOutAt,
                'note' => (string) $request->input('note'),
            ]);

            // 2) breaks更新（削除 → 更新 → 追加 の順で安全に）
            $breaksInput = $request->input('breaks', []);

            // 2-1) フォームに残っている「既存休憩ID」だけを集める（削除判定用）
            $keepExistingIds = [];

            if (is_array($breaksInput)) {
                foreach ($breaksInput as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $breakId = $row['id'] ?? null;
                    $start = $row['start'] ?? null;
                    $end = $row['end'] ?? null;

                    $startEmpty = $start === null || $start === '';
                    $endEmpty = $end === null || $end === '';

                    // 既存IDがあり、両方空なら「削除扱い」なので keep しない
                    if (is_numeric($breakId) && $startEmpty && $endEmpty) {
                        continue;
                    }

                    // 両方空（追加用の空行など）も keep しない
                    if ($startEmpty && $endEmpty) {
                        continue;
                    }

                    // 片方だけ入力は Request 側で弾く想定

                    // 既存IDなら keep に入れる
                    if (is_numeric($breakId)) {
                        $keepExistingIds[] = (int) $breakId;
                    }
                }
            }

            // 2-2) 先に削除（フォームに残っていない既存休憩を削除）
            BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->when(count($keepExistingIds) > 0, function ($q) use ($keepExistingIds) {
                    $q->whereNotIn('id', $keepExistingIds);
                })
                ->delete();

            // 2-3) 次に更新・追加
            if (is_array($breaksInput)) {
                foreach ($breaksInput as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $breakId = $row['id'] ?? null;
                    $start = $row['start'] ?? null;
                    $end = $row['end'] ?? null;

                    $startEmpty = $start === null || $start === '';
                    $endEmpty = $end === null || $end === '';

                    // 両方空は無視（追加用の空行など）
                    if ($startEmpty && $endEmpty) {
                        continue;
                    }

                    // 既存IDがあり、両方空は削除扱いだが、削除は 2-2 で終わっているので無視
                    if (is_numeric($breakId) && $startEmpty && $endEmpty) {
                        continue;
                    }

                    // 片方だけ入力は Request 側で弾く想定

                    $startAt = CarbonImmutable::createFromFormat(
                        'Y-m-d H:i',
                        $workDate . ' ' . (string) $start
                    );

                    $endAt = CarbonImmutable::createFromFormat(
                        'Y-m-d H:i',
                        $workDate . ' ' . (string) $end
                    );

                    // id があれば更新
                    if (is_numeric($breakId)) {
                        BreakTime::query()
                            ->where('id', (int) $breakId)
                            ->where('attendance_id', $attendance->id)
                            ->update([
                                'break_start_at' => $startAt,
                                'break_end_at' => $endAt,
                            ]);
                        continue;
                    }

                    // id が無ければ新規作成（追加）
                    BreakTime::query()->create([
                        'attendance_id' => $attendance->id,
                        'break_start_at' => $startAt,
                        'break_end_at' => $endAt,
                    ]);
                }
            }

            // 3) 承認待ち申請があれば削除（却下ステータスが無いため）
            $pendingRequests = CorrectionRequest::query()
                ->where('attendance_id', $attendance->id)
                ->where('status', CorrectionRequest::STATUS_PENDING)
                ->get();

            foreach ($pendingRequests as $pending) {
                $pending->breaks()->delete();
                $pending->delete();
            }
        });

        return redirect()->route('admin.attendance.list', [
            'date' => $attendance->work_date->toDateString(),
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

    // app/Http/Controllers/AdminAttendanceController.php

    public function showStaffList(Request $request, int $id): View
    {
        $user = User::query()->findOrFail($id);

        $month = (string) $request->query('month', '');
        [$base, $start, $end] = $this->resolveMonthRange($month);

        $rows = $this->buildMonthlyRows($user->id, $start, $end);

        return view('admin.attendance.staff', [
            'user' => $user,

            // 一般側と同じ
            'monthLabel' => $base->format('Y/m'),
            'prevMonth' => $base->subMonth()->format('Y-m'),
            'nextMonth' => $base->addMonth()->format('Y-m'),
            'rows' => $rows,

            // 管理者側の詳細ルート名（Bladeで使う）
            'detailRouteName' => 'admin.attendance.show',
        ]);
    }

    public function exportStaffCsv(Request $request, int $id): StreamedResponse
    {
        $user = User::query()->findOrFail($id);

        $month = (string) $request->query('month', '');
        [$base, $start, $end] = $this->resolveMonthRange($month);

        $rows = $this->buildMonthlyRows($user->id, $start, $end);

        $fileMonth = $base->format('Y-m');
        $fileName = '勤怠_' . $user->name . '_' . $fileMonth . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM（Excel対策）
            fwrite($out, "\xEF\xBB\xBF");

            // ヘッダー
            fputcsv($out, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    (string) $row['dateLabel'],
                    (string) $row['clockIn'],
                    (string) $row['clockOut'],
                    (string) $row['breakTotal'],
                    (string) $row['workTotal'],
                ]);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // app/Http/Controllers/AdminAttendanceController.php

    private function resolveMonthRange(string $month): array
    {
        $now = CarbonImmutable::now();

        $base = preg_match('/^\d{4}-\d{2}$/', $month) === 1
            ? CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()
            : $now->startOfMonth();

        $start = $base->startOfMonth();
        $end = $base->endOfMonth();

        return [$base, $start, $end];
    }

    private function buildMonthlyRows(int $userId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $attendances = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        // work_date => Attendance
        $attendanceByDate = [];
        $attendanceIds = [];

        foreach ($attendances as $attendance) {
            $workDate = $attendance->work_date instanceof \DateTimeInterface
                ? CarbonImmutable::parse($attendance->work_date)->toDateString()
                : (string) $attendance->work_date;

            $attendanceByDate[$workDate] = $attendance;
            $attendanceIds[] = $attendance->id;
        }

        // attendance_id => BreakTime[]
        $breaksByAttendanceId = [];
        if (!empty($attendanceIds)) {
            $breaks = BreakTime::query()
                ->whereIn('attendance_id', $attendanceIds)
                ->get();

            foreach ($breaks as $break) {
                $breaksByAttendanceId[$break->attendance_id][] = $break;
            }
        }

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        $rows = [];
        $cursor = $start;

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $attendance = $attendanceByDate[$date] ?? null;

            $clockIn = '';
            $clockOut = '';
            $breakTotal = '';
            $workTotal = '';
            $attendanceId = null;

            if ($attendance !== null) {
                $attendanceId = $attendance->id;

                if ($attendance->clock_in_at !== null) {
                    $clockIn = CarbonImmutable::parse($attendance->clock_in_at)->format('H:i');
                }
                if ($attendance->clock_out_at !== null) {
                    $clockOut = CarbonImmutable::parse($attendance->clock_out_at)->format('H:i');
                }

                $breakSeconds = 0;
                $breaks = $breaksByAttendanceId[$attendance->id] ?? [];

                foreach ($breaks as $break) {
                    if ($break->break_start_at === null || $break->break_end_at === null) {
                        continue;
                    }

                    $startAt = CarbonImmutable::parse($break->break_start_at);
                    $endAt = CarbonImmutable::parse($break->break_end_at);

                    if ($endAt->lessThan($startAt)) {
                        continue;
                    }

                    $breakSeconds += $startAt->diffInSeconds($endAt);
                }

                if ($breakSeconds > 0) {
                    $breakTotal = $this->formatDuration($breakSeconds);
                }

                if ($attendance->clock_in_at !== null && $attendance->clock_out_at !== null) {
                    $clockInAt = CarbonImmutable::parse($attendance->clock_in_at);
                    $clockOutAt = CarbonImmutable::parse($attendance->clock_out_at);

                    if ($clockOutAt->gte($clockInAt)) {
                        $workSeconds = $clockInAt->diffInSeconds($clockOutAt) - $breakSeconds;
                        if ($workSeconds < 0) {
                            $workSeconds = 0;
                        }
                        $workTotal = $this->formatDuration($workSeconds);
                    }
                }
            }

            $rows[] = [
                'dateLabel' => $cursor->format('m/d') . '(' . $weekdays[$cursor->dayOfWeek] . ')',
                'clockIn' => $clockIn,
                'clockOut' => $clockOut,
                'breakTotal' => $breakTotal,
                'workTotal' => $workTotal,
                'attendanceId' => $attendanceId,
            ];

            $cursor = $cursor->addDay();
        }

        return $rows;
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
    }
}
