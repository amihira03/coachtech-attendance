@extends('layouts.app')

@section('title', '勤怠詳細')

@section('content')
    <h1>勤怠詳細</h1>

    <p>URLの {id} を使って詳細を表示する画面です。</p>

    <p>ここに勤怠詳細テーブル・修正申請フォームが入ります。</p>

    <form action="{{ route('attendance.detail.store', ['id' => $id ?? 1]) }}" method="POST">
        @csrf
        <button type="submit">修正</button>
    </form>

    <p><a href="{{ route('attendance.list') }}">勤怠一覧へ戻る</a></p>
@endsection
