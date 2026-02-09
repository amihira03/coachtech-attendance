@extends('layouts.app')

@section('title', '会員登録')

@section('content')
    <h1>会員登録</h1>

    <form action="{{ route('register') }}" method="POST">
        @csrf

        <div>
            <label for="name">名前</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}">
        </div>

        <div>
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
        </div>

        <div>
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password">
        </div>

        <div>
            <label for="password_confirmation">パスワード確認</label>
            <input id="password_confirmation" type="password" name="password_confirmation">
        </div>

        <button type="submit">登録する</button>
    </form>

    <p><a href="{{ route('login') }}">ログインはこちら</a></p>
@endsection
