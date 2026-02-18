<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'COACHTECH Attendance - Admin')</title>

    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layouts/admin-header.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header-inner">
            <a class="header-logo" href="{{ route('admin.attendance.list') }}">
                <img src="{{ asset('images/logo.png') }}" alt="COACHTECH">
            </a>

            @auth
                @if (auth()->user()?->is_admin && !request()->routeIs('admin.login'))
                    <nav class="header-nav">
                        <a class="header-link" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                        <a class="header-link" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                        <a class="header-link" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>

                        <form class="header-logout" action="{{ route('logout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="admin_logout" value="1">
                            <button class="header-button" type="submit">ログアウト</button>
                        </form>
                    </nav>
                @endif
            @endauth
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>

</body>

</html>
