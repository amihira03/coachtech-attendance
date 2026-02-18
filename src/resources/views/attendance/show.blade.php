@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/show.css') }}">
@endsection

@section('content')
    <div class="attendance-detail">
        <div class="attendance-detail-inner">
            <h1 class="attendance-detail-title">勤怠詳細</h1>

            @if ($isPending)

                <div class="attendance-detail-table-wrap">
                    <table class="attendance-detail-table">
                        <tbody>
                            <tr>
                                <th class="attendance-detail-th">名前</th>
                                <td class="attendance-detail-td">{{ $userName }}</td>
                            </tr>
                            <tr>
                                <th class="attendance-detail-th">日付</th>
                                <td class="attendance-detail-td">{{ $displayDateLabel }}</td>
                            </tr>
                            <tr>
                                <th class="attendance-detail-th">出勤・退勤</th>
                                <td class="attendance-detail-td">
                                    <div class="attendance-detail-time-row">
                                        <span class="attendance-detail-break-time">{{ $display['clockIn'] }}</span>
                                        <span class="attendance-detail-break-sep">〜</span>
                                        <span class="attendance-detail-break-time">{{ $display['clockOut'] }}</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th class="attendance-detail-th">休憩</th>
                                <td class="attendance-detail-td">
                                    @if (!empty($display['breakRows']))
                                        <div class="attendance-detail-breaks">
                                            @foreach ($display['breakRows'] as $row)
                                                <div class="attendance-detail-break-row">
                                                    <span class="attendance-detail-break-time">{{ $row['start'] }}</span>
                                                    <span class="attendance-detail-break-sep">〜</span>
                                                    <span class="attendance-detail-break-time">{{ $row['end'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="attendance-detail-empty">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th class="attendance-detail-th">備考</th>
                                <td class="attendance-detail-td">
                                    @if (($display['note'] ?? '') !== '')
                                        <div class="attendance-detail-note">{{ $display['note'] }}</div>
                                    @else
                                        <span class="attendance-detail-empty">—</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="attendance-detail-message">
                    承認待ちのため修正はできません。
                </p>
            @else
                @php
                    $defaultBreaks = [];
                    foreach ($display['breakRows'] ?? [] as $row) {
                        $defaultBreaks[] = [
                            'start' => $row['start'] ?? '',
                            'end' => $row['end'] ?? '',
                        ];
                    }

                    $breakInputs = old('breaks', $defaultBreaks);
                    $clockInOld = old('clock_in_at', $display['clockIn'] ?? '');
                    $clockOutOld = old('clock_out_at', $display['clockOut'] ?? '');
                    $noteOld = old('note', $display['note'] ?? '');
                @endphp

                <form class="attendance-detail-form"
                    action="{{ route('attendance.detail.store', ['id' => $attendanceId]) }}" method="POST">
                    @csrf

                    <div class="attendance-detail-table-wrap">
                        <table class="attendance-detail-table">
                            <tbody>
                                <tr>
                                    <th class="attendance-detail-th">名前</th>
                                    <td class="attendance-detail-td">{{ $userName }}</td>
                                </tr>
                                <tr>
                                    <th class="attendance-detail-th">日付</th>
                                    <td class="attendance-detail-td">{{ $displayDateLabel }}</td>
                                </tr>
                                <tr>
                                    <th class="attendance-detail-th">出勤・退勤</th>
                                    <td class="attendance-detail-td">
                                        <div class="attendance-detail-time-row">
                                            <input class="attendance-detail-input attendance-detail-input--time"
                                                type="time" name="clock_in_at" value="{{ $clockInOld }}">
                                            <span class="attendance-detail-break-sep">〜</span>
                                            <input class="attendance-detail-input attendance-detail-input--time"
                                                type="time" name="clock_out_at" value="{{ $clockOutOld }}">
                                        </div>

                                        @error('clock_in_at')
                                            <p class="attendance-detail-error">{{ $message }}</p>
                                        @enderror
                                        @error('clock_out_at')
                                            <p class="attendance-detail-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <th class="attendance-detail-th">休憩</th>
                                    <td class="attendance-detail-td">
                                        <div class="attendance-detail-breaks">
                                            @foreach ($breakInputs as $i => $row)
                                                <div class="attendance-detail-break-row">
                                                    <input class="attendance-detail-input attendance-detail-input--time"
                                                        type="time" name="breaks[{{ $i }}][start]"
                                                        value="{{ $row['start'] ?? '' }}">
                                                    <span class="attendance-detail-break-sep">〜</span>
                                                    <input class="attendance-detail-input attendance-detail-input--time"
                                                        type="time" name="breaks[{{ $i }}][end]"
                                                        value="{{ $row['end'] ?? '' }}">
                                                </div>
                                            @endforeach
                                        </div>

                                        @error('breaks')
                                            <p class="attendance-detail-error">{{ $message }}</p>
                                        @enderror
                                        @error('breaks.*.start')
                                            <p class="attendance-detail-error">{{ $message }}</p>
                                        @enderror
                                        @error('breaks.*.end')
                                            <p class="attendance-detail-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                                <tr>
                                    <th class="attendance-detail-th">備考</th>
                                    <td class="attendance-detail-td">
                                        <textarea class="attendance-detail-textarea" name="note" rows="4">{{ $noteOld }}</textarea>
                                        @error('note')
                                            <p class="attendance-detail-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="attendance-detail-actions">
                        <button class="attendance-detail-submit" type="submit">
                            修正
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
