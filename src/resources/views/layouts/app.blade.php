<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>

    <div class="app">

        <header class="header">
            <img class="header-logo" src="{{ asset('image/logo.png') }}" alt="COACHTECH">
            <nav class="header__nav">
                <div class="hamburger" id="js-hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <div class="header__nav-inner" id="js-nav-menu">
                @auth('admin')
                    <form class="header__nav-form" action="/admin/logout" method="post">
                    @csrf
                        <a class="header__nav-link" href="">勤怠一覧</a>
                        <a class="header__nav-link" href="">スタッフ一覧</a>
                        <a class="header__nav-link" href="">申請一覧</a>
                        <button class="logout__btn">ログアウト</button>
                    </form>
                @elseauth
                    <form class="header__nav-form" action="/logout" method="post">
                    @csrf
                        <a class="header__nav-link" href="{{ route('attendance.show') }}">勤怠</a>
                        <a class="header__nav-link" href="{{ route('attendance.index')}}">勤怠一覧</a>
                        <a class="header__nav-link" href="{{ route('request.list') }}">申請</a>
                        <button class="logout__btn">ログアウト</button>
                    </form>
                @endauth
                </div>
            </nav>
        </header>

        @yield('content')

    </div>

<script src="{{ asset('js/hamburger_menu.js') }}"></script>
@yield('script')

</body>
</html>