@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@section('content')
    <h1>スタッフ一覧</h1>

    <p>ここにスタッフ一覧テーブルが入ります。</p>

    <p>
        スタッフ別勤怠（仮）：
        <a href="{{ route('admin.attendance.staff', ['id' => 1]) }}">スタッフ別勤怠へ（id=1）</a>
    </p>
@endsection
