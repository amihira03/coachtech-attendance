<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ここではバリデーションに集中。
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in_at' => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i'],

            'breaks' => ['nullable', 'array'],
            'breaks.*.id' => ['nullable', 'integer'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],

            'note' => ['required'],
        ];
    }

    public function attributes(): array
    {
        return [
            'clock_in_at' => '出勤時間',
            'clock_out_at' => '退勤時間',
            'note' => '備考',
            'breaks.*.start' => '休憩開始',
            'breaks.*.end' => '休憩終了',
        ];
    }

    public function messages(): array
    {
        return [
            // 要件で文言指定があるものは固定
            'note.required' => '備考を記入してください',
            'clock_in_at.required' => '出勤時間を入力してください',
            'clock_out_at.required' => '退勤時間を入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $attendance = $this->targetAttendance();
            if ($attendance === null) {
                return;
            }

            $workDate = CarbonImmutable::parse($attendance->work_date)->format('Y-m-d');

            $clockIn = $this->toDateTimeOrNull($workDate, $this->input('clock_in_at'));
            $clockOut = $this->toDateTimeOrNull($workDate, $this->input('clock_out_at'));

            // 出勤・退勤の前後不整合
            if ($clockIn !== null && $clockOut !== null && $clockOut->lessThanOrEqualTo($clockIn)) {
                $validator->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $breaks = $this->input('breaks', []);
            if (!is_array($breaks)) {
                return;
            }

            // ★追加：重なりチェック用に「有効な休憩区間」を集める
            $validRanges = [];

            foreach ($breaks as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $startRaw = $row['start'] ?? null;
                $endRaw = $row['end'] ?? null;

                $startEmpty = $startRaw === null || $startRaw === '';
                $endEmpty = $endRaw === null || $endRaw === '';

                // 両方空は無視（最後の空行など）
                if ($startEmpty && $endEmpty) {
                    continue;
                }

                // 片方だけ入力は不適切扱い（要件文言に寄せる）
                if ($startEmpty || $endEmpty) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                $breakStart = $this->toDateTimeOrNull($workDate, (string) $startRaw);
                $breakEnd = $this->toDateTimeOrNull($workDate, (string) $endRaw);

                if ($breakStart === null || $breakEnd === null) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩開始 > 休憩終了（等しいもNG）
                if ($breakEnd->lessThanOrEqualTo($breakStart)) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩開始が出勤より前
                if ($clockIn !== null && $breakStart->lessThan($clockIn)) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩開始が退勤以上
                if ($clockOut !== null && $breakStart->greaterThanOrEqualTo($clockOut)) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩終了が退勤より後（要件で文言が別）
                if ($clockOut !== null && $breakEnd->greaterThan($clockOut)) {
                    $validator->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    continue;
                }

                // ★ここまで通った休憩だけ「有効」として重なりチェック対象に入れる
                $validRanges[] = [
                    'i' => $i,              // エラー表示用に何行目か保持
                    'start' => $breakStart,
                    'end' => $breakEnd,
                ];
            }

            // ★追加：休憩同士の重なりチェック（最大2件想定でもOK／一般化）
            if (count($validRanges) >= 2) {
                // 念のため開始時刻順にソート（入れ替え無し想定でも安全）
                usort($validRanges, fn($a, $b) => $a['start'] <=> $b['start']);

                for ($k = 0; $k < count($validRanges) - 1; $k++) {
                    $current = $validRanges[$k];
                    $next = $validRanges[$k + 1];

                    // 次の開始が現在の終了より前なら重なり
                    // ※ぴったり接する（end == next.start）はOKにするため lessThan を使用
                    if ($next['start']->lessThan($current['end'])) {
                        $validator->errors()->add("breaks.{$next['i']}.start", '休憩時間が不適切な値です');
                        break;
                    }
                }
            }
        });
    }

    private function targetAttendance(): ?Attendance
    {
        $id = $this->route('id');

        if (!is_numeric($id)) {
            return null;
        }

        // 管理者用：user_idで絞らない
        return Attendance::query()
            ->where('id', (int) $id)
            ->first();
    }

    private function toDateTimeOrNull(string $date, mixed $time): ?CarbonImmutable
    {
        if (!is_string($time) || $time === '') {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time) ?: null;
    }
}
