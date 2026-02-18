@extends('layouts.app')

@section('title', '申請一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/corrections/list.css') }}">
@endsection

@section('content')
    @php
        $baseUrl = url('/stamp_correction_request/list');

        $isPendingTab = ($tab ?? 'pending') === 'pending';
        $isApprovedTab = ($tab ?? 'pending') === 'approved';
    @endphp

    <div class="corrections-list">
        <div class="corrections-list-inner">
            <h1 class="corrections-list-title">申請一覧</h1>

            <nav class="corrections-list-tabs">
                <a class="corrections-list-tab {{ $isPendingTab ? 'corrections-list-tab--active' : '' }}"
                    href="{{ $baseUrl }}?tab=pending">
                    承認待ち
                </a>
                <a class="corrections-list-tab {{ $isApprovedTab ? 'corrections-list-tab--active' : '' }}"
                    href="{{ $baseUrl }}?tab=approved">
                    承認済み
                </a>
            </nav>

            <div class="corrections-list-table-wrap">
                @if (!empty($rows))
                    <table class="corrections-list-table">
                        <thead>
                            <tr>
                                <th class="corrections-list-th">状態</th>
                                <th class="corrections-list-th">名前</th>
                                <th class="corrections-list-th">対象日時</th>
                                <th class="corrections-list-th">申請理由</th>
                                <th class="corrections-list-th">申請日時</th>
                                <th class="corrections-list-th">詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="corrections-list-td">{{ $row['statusLabel'] }}</td>
                                    <td class="corrections-list-td">{{ $row['userName'] }}</td>
                                    <td class="corrections-list-td">{{ $row['workDateLabel'] }}</td>
                                    <td class="corrections-list-td">
                                        @if (($row['requestedNote'] ?? '') !== '')
                                            <div class="corrections-list-note">{{ $row['requestedNote'] }}</div>
                                        @else
                                            <span class="corrections-list-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="corrections-list-td">{{ $row['requestedAtLabel'] }}</td>
                                    <td class="corrections-list-td">
                                        @if (!empty($row['attendanceId']))
                                            <a class="corrections-list-detail"
                                                href="{{ route('attendance.detail', ['id' => $row['attendanceId']]) }}">
                                                詳細
                                            </a>
                                        @else
                                            <span class="corrections-list-empty">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="corrections-list-empty-box">
                        該当する申請がありません
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
