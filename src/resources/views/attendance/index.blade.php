@extends('layouts.app')

@section('title', '勤怠')

@section('content')
    <h1>勤怠（打刻）</h1>

    <p>ここに日付・時刻表示、出勤/休憩/退勤ボタンが入ります。</p>

    <form action="{{ route('attendance.store') }}" method="POST">
        @csrf
        <button type="submit">打刻（仮）</button>
    </form>
@endsection
