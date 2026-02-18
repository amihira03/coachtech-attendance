<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'COACHTECH Attendance')</title>

    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layouts/header.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header-inner">
            <a class="header-logo" href="{{ route('attendance.index') }}">
                <img src="{{ asset('images/logo.png') }}" alt="COACHTECH">
            </a>

            @auth
                <nav class="header-nav">
                    @if (request()->routeIs('attendance.index') && ($isFinished ?? false))
                        <a class="header-link" href="{{ route('attendance.list') }}">
                            今月の出勤一覧
                        </a>
                        <a class="header-link" href="{{ route('stamp_correction_request.list') }}">
                            申請一覧
                        </a>
                    @else
                        <a class="header-link" href="{{ route('attendance.index') }}">勤怠</a>
                        <a class="header-link" href="{{ route('attendance.list') }}">勤怠一覧</a>
                        <a class="header-link" href="{{ route('stamp_correction_request.list') }}">申請</a>
                    @endif

                    <form class="header-logout" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="header-button" type="submit">ログアウト</button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>
</body>

</html>
