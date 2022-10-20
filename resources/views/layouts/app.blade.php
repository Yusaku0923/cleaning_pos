<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    @livewireStyles
</head>
<body>
    <div id="app">
        <main class="py-4">
            <div class="col-12 mb-4 px-2">
                <div class="card py-2">
                    <div class="d-flex justify-content-between">
                        <div class="col-4 px-5 text-start">担当者：{{ session('manager_name') ?? '設定されていません' }}</div>
                        <div class="col-4 text-center fw-bold">{{ $title ?? 'システム' }}</div>
                        <div class="col-4 px-5 text-end">0000年00月00日 00時00分</div>
                    </div>
                </div>
            </div>
            @yield('content')
        </main>
    </div>
</body>
</html>
