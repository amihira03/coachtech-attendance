<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in_at' => ['required', 'date_format:H:i'],
            'clock_out_at' => ['required', 'date_format:H:i'],

            'breaks' => ['nullable', 'array'],
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

            if ($clockIn !== null && $clockOut !== null && $clockOut->lessThanOrEqualTo($clockIn)) {
                $validator->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $breaks = $this->input('breaks', []);
            if (!is_array($breaks)) {
                return;
            }

            $validRanges = [];

            foreach ($breaks as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                $startRaw = $row['start'] ?? null;
                $endRaw = $row['end'] ?? null;

                $startEmpty = $startRaw === null || $startRaw === '';
                $endEmpty = $endRaw === null || $endRaw === '';

                if ($startEmpty && $endEmpty) {
                    continue;
                }

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

                if ($breakEnd->lessThanOrEqualTo($breakStart)) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                if ($clockIn !== null && $breakStart->lessThan($clockIn)) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                if ($clockOut !== null && $breakStart->greaterThanOrEqualTo($clockOut)) {
                    $validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    continue;
                }

                if ($clockOut !== null && $breakEnd->greaterThan($clockOut)) {
                    $validator->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                    continue;
                }

                $validRanges[] = [
                    'i' => $i,
                    'start' => $breakStart,
                    'end' => $breakEnd,
                ];
            }

            if (count($validRanges) >= 2) {
                usort($validRanges, fn($a, $b) => $a['start'] <=> $b['start']);

                for ($k = 0; $k < count($validRanges) - 1; $k++) {
                    $current = $validRanges[$k];
                    $next = $validRanges[$k + 1];

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

        return Attendance::query()
            ->where('id', (int) $id)
            ->where('user_id', auth()->id())
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
