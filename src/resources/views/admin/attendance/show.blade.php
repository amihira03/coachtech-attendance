@extends('layouts.admin')

@section('title', '勤怠詳細（管理者）')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/show.css') }}">
@endsection

@section('content')
    <main class="admin-attendance-detail">
        <div class="admin-attendance-detail-inner">
            <h1 class="admin-attendance-detail-title">勤怠詳細</h1>

            @php
                // 出勤・退勤・備考（Controllerの表示値をベースに old() 優先）
                $clockInOld = old('clock_in_at', $displayClockIn ?? '');
                $clockOutOld = old('clock_out_at', $displayClockOut ?? '');
                $noteOld = old('note', $displayNote ?? '');

                // 休憩：Controllerの表示用（id/start/end）をフォーム用に整形し、
                //      さらに「追加用の空行」を常に1行足す
                $defaultBreaks = [];
                foreach ($displayBreaks ?? [] as $row) {
                    $defaultBreaks[] = [
                        'id' => $row['id'] ?? null,
                        'start' => $row['start'] ?? '',
                        'end' => $row['end'] ?? '',
                    ];
                }

                // ★常に追加用の空行を1つ足す（管理者は追加できる）
                $defaultBreaks[] = ['id' => null, 'start' => '', 'end' => ''];

                $breakInputs = old('breaks', $defaultBreaks);
            @endphp

            <form class="admin-attendance-detail-form"
                action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}" method="POST">
                @csrf

                <div class="admin-attendance-detail-table-wrap">
                    <table class="admin-attendance-detail-table">
                        <tbody>
                            <tr>
                                <th class="admin-attendance-detail-th">名前</th>
                                <td class="admin-attendance-detail-td">{{ $attendance->user->name }}</td>
                            </tr>

                            <tr>
                                <th class="admin-attendance-detail-th">日付</th>
                                <td class="admin-attendance-detail-td">{{ $attendance->work_date->format('Y年n月j日') }}</td>
                            </tr>

                            <tr>
                                <th class="admin-attendance-detail-th">出勤・退勤</th>
                                <td class="admin-attendance-detail-td">
                                    <div class="admin-attendance-detail-time-row">
                                        <input class="admin-attendance-detail-input admin-attendance-detail-input--time"
                                            type="time" name="clock_in_at" value="{{ $clockInOld }}">
                                        <span class="admin-attendance-detail-break-sep">〜</span>
                                        <input class="admin-attendance-detail-input admin-attendance-detail-input--time"
                                            type="time" name="clock_out_at" value="{{ $clockOutOld }}">
                                    </div>

                                    @error('clock_in_at')
                                        <p class="admin-attendance-detail-error">{{ $message }}</p>
                                    @enderror
                                    @error('clock_out_at')
                                        <p class="admin-attendance-detail-error">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>

                            <tr>
                                <th class="admin-attendance-detail-th">休憩</th>
                                <td class="admin-attendance-detail-td">
                                    <div class="admin-attendance-detail-breaks">
                                        @foreach ($breakInputs as $i => $row)
                                            <div class="admin-attendance-detail-break-row">
                                                <input type="hidden" name="breaks[{{ $i }}][id]"
                                                    value="{{ $row['id'] ?? '' }}">

                                                <input
                                                    class="admin-attendance-detail-input admin-attendance-detail-input--time"
                                                    type="time" name="breaks[{{ $i }}][start]"
                                                    value="{{ $row['start'] ?? '' }}">

                                                <span class="admin-attendance-detail-break-sep">〜</span>

                                                <input
                                                    class="admin-attendance-detail-input admin-attendance-detail-input--time"
                                                    type="time" name="breaks[{{ $i }}][end]"
                                                    value="{{ $row['end'] ?? '' }}">
                                            </div>
                                        @endforeach
                                    </div>

                                    @error('breaks')
                                        <p class="admin-attendance-detail-error">{{ $message }}</p>
                                    @enderror
                                    @error('breaks.*.start')
                                        <p class="admin-attendance-detail-error">{{ $message }}</p>
                                    @enderror
                                    @error('breaks.*.end')
                                        <p class="admin-attendance-detail-error">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>

                            <tr>
                                <th class="admin-attendance-detail-th">備考</th>
                                <td class="admin-attendance-detail-td">
                                    <textarea class="admin-attendance-detail-textarea" name="note" rows="4">{{ $noteOld }}</textarea>

                                    @error('note')
                                        <p class="admin-attendance-detail-error">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="admin-attendance-detail-actions">
                    <button class="admin-attendance-detail-submit" type="submit">
                        修正
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection
