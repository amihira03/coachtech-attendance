@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('title', 'メール認証')

@section('content')
    <div class="verify-notice">

        <p class="verify-notice-text">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        <p class="verify-notice-button-wrap">
            <a class="verify-notice-button" href="http://localhost:8025" target="_blank" rel="noopener">
                認証はこちらから
            </a>
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="verify-notice-resend" type="submit">認証メールを再送する</button>
        </form>

        @if (session('status') === 'verification-link-sent')
            <p class="verify-notice-status">認証メールを再送しました</p>
        @endif
    </div>
@endsection
