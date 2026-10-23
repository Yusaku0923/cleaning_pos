<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fullscreen For Safari -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="レジアプリ{{ config('app.env') === 'development' ? '（開発）': '' }}">
    <meta name="viewport" content="width=320,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,minimal-ui">

    <!-- icon -->
    <link rel="apple-touch-icon" href="{{ asset('img/icon.png') }}" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts (Vite で同梱。CDN からは読まない) -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <script src="{{ asset('js/common.js') }}" defer></script>
    <script type="text/javascript" src="{{ asset('js/epos-2.22.0.js') }}"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

</head>
<body>
    <div id="app" class="{{ session('theme_body') ?? session('theme_body') }}" style="height: 100dvh;">
        @if (!request()->is('*customer_display*'))
        <main class="{{ request()->is('delivery/sp*') ? '' : 'pt-4' }} position-relative">
            @if (!request()->is('delivery/sp*'))
            <div class="col-12 mb-4 px-2 position-sticky">
                @if ( !request()->is('*login*') )
                <a href="{{ route('home') }}" class="card text-decoration-none text-body border border-2 border-dark {{ session('theme_header') ? session('theme_header') : '' }}">
                    <div class="d-flex justify-content-between  {{ session('theme_header') ? session('theme_header').'-header' : '' }}">
                        <div class="col-4 px-5 text-start fs-20" style="line-height: 43px">担当者：<span class="{{ is_null(session('manager_name')) ? 'text-danger fw-bold': '' }}">{{ session('manager_name') ?? '設定されていません' }}</span></div>
                        <div class="col-4 text-center fs-20" style="line-height: 43px">{{ $title ?? 'システム' }}</div>
                        <div class="col-4 px-5 text-end fs-20" id="RealtimeClockArea" style="line-height: 43px"></div>
                    </div>
                </a>
                @endif
            </div>
            @endif
            @yield('content')
        </main>
        @else
        @yield('content')
        @endif
    </div>
</body>
</html>

<script>
    window.addEventListener('load', (event) => {
        document.addEventListener("dblclick", function(e){ e.preventDefault();}, { passive: false });
    });
</script>
