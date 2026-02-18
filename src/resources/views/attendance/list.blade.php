@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
@endsection

@section('content')
    <div class="attendance-list">
        <div class="attendance-list-inner">
            <h1 class="attendance-list-title">勤怠一覧</h1>

            <div class="attendance-list-month-nav">
                <a class="attendance-list-month-btn" href="{{ route('attendance.list', ['month' => $prevMonth]) }}">
                    <img src="{{ asset('images/leftbutton.svg') }}" alt="前月" class="attendance-list-arrow-icon">
                    前月
                </a>

                <div class="attendance-list-month-label">
                    <img src="{{ asset('images/calendar-icon.svg') }}" alt="calendar" class="attendance-list-calendar-icon">
                    {{ $monthLabel }}
                </div>

                <a class="attendance-list-month-btn" href="{{ route('attendance.list', ['month' => $nextMonth]) }}">
                    翌月
                    <img src="{{ asset('images/rightbutton.svg') }}" alt="翌月" class="attendance-list-arrow-icon">
                </a>
            </div>

            <div class="attendance-list-table-wrap">
                <table class="attendance-list-table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>休憩</th>
                            <th>合計</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['dateLabel'] }}</td>
                                <td>{{ $row['clockIn'] }}</td>
                                <td>{{ $row['clockOut'] }}</td>
                                <td>{{ $row['breakTotal'] }}</td>
                                <td>{{ $row['workTotal'] }}</td>
                                <td>
                                    @if ($row['attendanceId'])
                                        <a class="attendance-list-detail"
                                            href="{{ route('attendance.detail', ['id' => $row['attendanceId']]) }}">
                                            詳細
                                        </a>
                                    @else
                                        <span class="attendance-list-detail attendance-list-detail--disabled">
                                            詳細
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
