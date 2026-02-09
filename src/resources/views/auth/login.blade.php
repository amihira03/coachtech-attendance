@extends('layouts.app')

@section('title', 'ログイン')

@section('content')
    <h1>ログイン</h1>

    <form action="{{ route('login') }}" method="POST">
        @csrf

        <div>
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
        </div>

        <div>
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password">
        </div>

        <button type="submit">ログインする</button>
    </form>

    <p><a href="{{ route('register') }}">会員登録はこちら</a></p>
@endsection
