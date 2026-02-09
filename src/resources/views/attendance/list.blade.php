@extends('layouts.app')

@section('title', '勤怠一覧')

@section('content')
    <h1>勤怠一覧</h1>

    <p>ここに日付切替・一覧テーブルが入ります。</p>

    <p>
        例：詳細リンク（仮）
        <a href="{{ route('attendance.detail', ['id' => 1]) }}">勤怠詳細へ（id=1）</a>
    </p>
@endsection
