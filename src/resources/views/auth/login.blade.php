@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('title', 'ログイン')

@section('content')
    <div class="auth-login">
        <h1 class="auth-login-title">ログイン</h1>

        {{-- ログイン失敗（資格情報不一致） --}}
        @error('login')
            <p class="auth-login-error">{{ $message }}</p>
        @enderror

        <form class="auth-login-form" action="{{ route('login') }}" method="POST">
            @csrf

            <div class="auth-login-field">
                <label class="auth-login-label" for="email">メールアドレス</label>
                <input class="auth-login-input" id="email" type="email" name="email" value="{{ old('email') }}">

                @error('email')
                    <p class="auth-login-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="auth-login-field">
                <label class="auth-login-label" for="password">パスワード</label>
                <input class="auth-login-input" id="password" type="password" name="password">

                @error('password')
                    <p class="auth-login-field-error">{{ $message }}</p>
                @enderror
            </div>

            <button class="auth-login-submit" type="submit">ログインする</button>
        </form>

        <p class="auth-login-register">
            <a class="auth-login-link" href="{{ route('register') }}">会員登録はこちら</a>
        </p>
    </div>
@endsection
