@extends('layouts.admin')

@section('title', '申請承認（管理者）')

@section('content')
    <h1>申請承認（管理者）</h1>

    <p>ここに申請内容の詳細が入ります。</p>

    <p>
        承認対象ID（仮）：
        {{ $attendance_correct_request_id ?? '（未設定）' }}
    </p>

    <form
        action="{{ route('stamp_correction_request.approve.confirm', ['attendance_correct_request_id' => $attendance_correct_request_id ?? 1]) }}"
        method="POST">
        @csrf
        <button type="submit">承認する（仮）</button>
    </form>

    <p>
        <a href="{{ route('stamp_correction_request.list') }}">申請一覧へ戻る</a>
    </p>
@endsection
