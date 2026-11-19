<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @stack('head')
    <title>@yield('title', 'Issues') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <a href="{{ route('issues.index') }}" class="brand">{{ config('app.name') }}</a>
            <a href="{{ route('issues.create') }}" class="button">New issue</a>
        </div>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
