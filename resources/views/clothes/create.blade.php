@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-10 mx-auto">

        {{-- @livewire('create-clothes-menu')
        @livewireScripts --}}

        <div>
            <div class="card col-12 py-3 mb-4 h4 text-center">
                カテゴリー
            </div>
            <div class="col-12 menu-columns justify-content-between">
                @if (!true)
                <a href="{{ route('clothes.create') }}" class="card menu-card col-12 mx-auto text-dark text-decoration-none">
                    <span class="fw-light text-secondary return-icon text-center mx-auto"><i class="fa-solid fa-rotate-left"></i></span>
                    <span class="text-center mx-auto">もどる</span>
                </a>
                @endif
                <button type="button" class="card menu-card col-12 mx-auto" data-bs-toggle="modal" data-bs-target="#menu-create-modal">
                    <span class="fw-light text-primary menu-icon text-center mx-auto">＋</span>
                    <span class="text-center mx-auto">カテゴリーを追加する</span>
                </button>
                @foreach ($cards as $card)
                @if (true)
                <button class="card menu-card col-12 mx-auto">
                    <div class="category-label-field position-relative">
                        <p class="category-label text-center position-absolute top-50 start-50 translate-middle">{{ $card->name }}</p>
                    </div>
                </button>
                @else
                <button class="card menu-card col-12 mx-auto">
                    <div class="clothes-label-upper-field">
                        <span class="clothes-label mx-auto text-center">{{ $card->name }}</span>
                    </div>
                    <div class="clothes-label-lower-field text-center">
                        <span class="price-label mx-auto text-center">{{ number_format($card->price) }}円</span>
                    </div>
                </button>
                @endif
                @endforeach

            </div>
            <!-- modal -->
            @include('modals.create_menu')
            <!-- modal -->
        </div>

    </div>
</div>
@endsection
