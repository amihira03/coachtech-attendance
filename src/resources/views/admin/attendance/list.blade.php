@extends('layouts.admin')

@section('title', '勤怠一覧（管理者）')

@section('content')
    <h1>勤怠一覧（管理者）</h1>

    <p>ここに日付切替・日次勤怠テーブルが入ります。</p>

    <p>
        勤怠詳細（仮）：
        <a href="{{ route('admin.attendance.show', ['id' => 1]) }}">勤怠詳細へ（id=1）</a>
    </p>
@endsection
