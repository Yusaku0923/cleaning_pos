<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- icon -->
    <link rel="apple-touch-icon" href="{{ public_path('img/icon.png') }}" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('js/common.js') }}" defer></script>
    <script type="text/javascript" src="{{ asset('js/epos-2.22.0.js') }}"></script>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @livewireStyles
</head>
<body>
    <div id="app" class="{{ session('theme_body') ?? session('theme_body') }}">
        <main class="pt-4 position-relative">
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
            @yield('content')
        </main>
    </div>
</body>
</html>

<script>
    window.addEventListener('load', (event) => {
        document.addEventListener("dblclick", function(e){ e.preventDefault();}, { passive: false });
    });
</script>
