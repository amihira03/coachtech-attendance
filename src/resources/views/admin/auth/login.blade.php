@extends('layouts.admin')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('title', '管理者ログイン')

@section('content')
    <div class="auth-login">
        <h1 class="auth-login-title">管理者ログイン</h1>

        {{-- ログイン失敗（資格情報不一致） --}}
        @error('login')
            <p class="auth-login-error">{{ $message }}</p>
        @enderror

        <form class="auth-login-form" action="{{ route('login') }}" method="POST">
            @csrf

            {{-- 管理者ログイン判定用フラグ --}}
            <input type="hidden" name="admin_login" value="1">

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

            <button class="auth-login-submit" type="submit">管理者ログインする</button>
        </form>
    </div>
@endsection
