@extends('layouts.admin')

@section('title', '申請一覧（管理者）')

@section('content')
    <h1>申請一覧</h1>

    <p>管理者向けの申請一覧（承認導線あり）を表示する想定です。</p>

    <nav>
        <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}">承認待ち</a>
        <span> / </span>
        <a href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}">承認済み</a>
    </nav>

    <table>
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>承認待ち（仮）</td>
                <td>スタッフ名（仮）</td>
                <td>2023/06/01（仮）</td>
                <td>遅延のため（仮）</td>
                <td>2023/06/02（仮）</td>
                <td>
                    <a href="{{ route('stamp_correction_request.approve.show', ['attendance_correct_request_id' => 1]) }}">
                        詳細
                    </a>
                </td>
            </tr>
        </tbody>
    </table>
@endsection
