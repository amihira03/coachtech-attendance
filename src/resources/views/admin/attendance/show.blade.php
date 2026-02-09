@extends('layouts.admin')

@section('title', '勤怠詳細（管理者）')

@section('content')
    <h1>勤怠詳細（管理者）</h1>

    <p>ここに勤怠詳細表示・管理者修正フォームが入ります。</p>

    <form action="{{ route('admin.attendance.update', ['id' => $id ?? 1]) }}" method="POST">
        @csrf
        <button type="submit">修正</button>
    </form>

    <p><a href="{{ route('admin.attendance.list') }}">一覧へ戻る</a></p>
@endsection
