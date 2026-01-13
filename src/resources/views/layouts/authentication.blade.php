<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/authentication.css') }}">
    @yield('css')
</head>

<body>

    <div class="app">

        <header class="header">
            <img class="header-logo" src="{{ asset('image/logo.png') }}" alt="COACHTECH">
        </header>

        @yield('content')

    </div>

</body>
</html>