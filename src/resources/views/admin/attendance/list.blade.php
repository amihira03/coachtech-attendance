@extends('layouts.admin')

@section('title', '勤怠一覧（管理者）')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/list.css') }}">
@endsection

@section('content')
    <div class="admin-list">
        <div class="admin-list-inner">
            <h1 class="admin-list-title">{{ $displayDate->format('Y年n月j日') }}の勤怠</h1>

            <div class="admin-list-daily-nav">
                <a class="admin-list-daily-btn"
                    href="{{ url('/admin/attendance/list') }}?date={{ $prevDate->toDateString() }}">
                    <img src="{{ asset('images/leftbutton.svg') }}" alt="前日" class="admin-list-arrow-icon">
                    前日
                </a>

                <div class="admin-list-daily-label">
                    <img src="{{ asset('images/calendar-icon.svg') }}" alt="calendar" class="admin-list-calendar-icon">
                    {{ $displayDate->format('Y/m/d') }}
                </div>

                <a class="admin-list-daily-btn"
                    href="{{ url('/admin/attendance/list') }}?date={{ $nextDate->toDateString() }}">
                    翌日
                    <img src="{{ asset('images/rightbutton.svg') }}" alt="翌日" class="admin-list-arrow-icon">
                </a>
            </div>

            <div class="admin-list-table-wrap">
                <table class="admin-list-table">
                    <thead>
                        <tr>
                            <th class="admin-list-th">名前</th>
                            <th class="admin-list-th">出勤</th>
                            <th class="admin-list-th">退勤</th>
                            <th class="admin-list-th">休憩</th>
                            <th class="admin-list-th">合計</th>
                            <th class="admin-list-th">詳細</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="admin-list-td">{{ $row['name'] }}</td>
                                <td class="admin-list-td">{{ $row['clock_in'] ?? '' }}</td>
                                <td class="admin-list-td">{{ $row['clock_out'] ?? '' }}</td>
                                <td class="admin-list-td">{{ $row['break_total'] ?? '' }}</td>
                                <td class="admin-list-td">{{ $row['work_total'] ?? '' }}</td>
                                <td class="admin-list-td">
                                    <a class="admin-list-detail-link"
                                        href="{{ url('/admin/attendance/' . $row['attendance_id']) }}">
                                        詳細
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="admin-list-empty-box" colspan="6">
                                    該当する勤怠がありません
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
