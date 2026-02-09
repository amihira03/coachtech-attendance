<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'COACHTECH Attendance - Admin')</title>

    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">

    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="{{ route('admin.attendance.list') }}">
                <img src="{{ asset('images/logo.png') }}" alt="COACHTECH">
            </a>

            @auth
                {{-- 念のため管理者だけに限定（admin配下に入るので通常は不要ですが安全） --}}
                @if (auth()->user()?->is_admin)
                    <nav class="header__nav">
                        <a class="header__link" href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                        <a class="header__link" href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                        <a class="header__link" href="{{ route('stamp_correction_request.list') }}">申請一覧</a>

                        <form class="header__logout" action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="header__button" type="submit">ログアウト</button>
                        </form>
                    </nav>
                @endif
            @endauth
        </div>
    </header>

    <main class="main">
        @yield('content')
    </main>

    @yield('js')
</body>

</html>
