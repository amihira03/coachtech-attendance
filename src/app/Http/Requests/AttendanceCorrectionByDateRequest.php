<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceCorrectionByDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}$/'],

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
            'clock_in_at.required' => '出勤時間を入力してください',
            'clock_out_at.required' => '退勤時間を入力してください',
            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $date = (string) $this->input('date', '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
                return;
            }

            $clockIn = $this->toDateTimeOrNull($date, $this->input('clock_in_at'));
            $clockOut = $this->toDateTimeOrNull($date, $this->input('clock_out_at'));

            if ($clockIn !== null && $clockOut !== null && $clockOut->lessThanOrEqualTo($clockIn)) {
                $validator->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $breaks = $this->input('breaks', []);
            if (!is_array($breaks)) {
                return;
            }

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

                $breakStart = $this->toDateTimeOrNull($date, (string) $startRaw);
                $breakEnd = $this->toDateTimeOrNull($date, (string) $endRaw);

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
            }
        });
    }

    private function toDateTimeOrNull(string $date, mixed $time): ?CarbonImmutable
    {
        if (!is_string($time) || $time === '') {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time) ?: null;
    }
}
