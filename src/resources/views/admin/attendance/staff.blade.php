@extends('layouts.admin')

@section('title', 'スタッフ別勤怠一覧')

@section('content')
    <h1>スタッフ別勤怠一覧（月次）</h1>

    <p>ここにスタッフ別の月次勤怠テーブルが入ります。</p>

    <p><a href="{{ route('admin.attendance.show', ['id' => 1]) }}">勤怠詳細へ（id=1）</a></p>
@endsection
