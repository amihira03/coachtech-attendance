@extends('layouts.admin')

@section('title', '管理者ログイン')

@section('content')
    <h1>管理者ログイン</h1>

    <form action="{{ url('/admin/login') }}" method="POST">
        @csrf

        <div>
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
        </div>

        <div>
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password">
        </div>

        <button type="submit">管理者ログインする</button>
    </form>
@endsection
