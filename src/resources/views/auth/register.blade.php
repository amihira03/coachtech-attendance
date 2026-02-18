@extends('layouts.app')

@section('title', '会員登録')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
    <div class="auth-register">
        <h1 class="auth-register-title">会員登録</h1>

        <form class="auth-register-form" action="{{ route('register') }}" method="POST">
            @csrf

            <div class="auth-register-field">
                <label class="auth-register-label" for="name">名前</label>
                <input class="auth-register-input" id="name" type="text" name="name" value="{{ old('name') }}">
                @error('name')
                    <p class="auth-register-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="auth-register-field">
                <label class="auth-register-label" for="email">メールアドレス</label>
                <input class="auth-register-input" id="email" type="email" name="email" value="{{ old('email') }}">
                @error('email')
                    <p class="auth-register-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="auth-register-field">
                <label class="auth-register-label" for="password">パスワード</label>
                <input class="auth-register-input" id="password" type="password" name="password">
                @error('password')
                    @if ($message !== 'パスワードと一致しません')
                        <p class="auth-register-field-error">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <div class="auth-register-field">
                <label class="auth-register-label" for="password_confirmation">パスワード確認</label>
                <input class="auth-register-input" id="password_confirmation" type="password" name="password_confirmation">
                @if ($errors->first('password') === 'パスワードと一致しません')
                    <p class="auth-register-field-error">{{ $errors->first('password') }}</p>
                @endif
            </div>

            <button class="auth-register-submit" type="submit">登録する</button>
        </form>

        <p class="auth-register-login">
            <a class="auth-register-link" href="{{ route('login') }}">ログインはこちら</a>
        </p>
    </div>
@endsection
