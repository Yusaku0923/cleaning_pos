@php
    $is_category = is_null($category_id);
@endphp
<div>
    <div class="card col-12 py-3 mb-4 h4 text-center">
        {{ $is_category ? 'カテゴリー': $category }}
    </div>
    <div class="col-12 menu-columns justify-content-between">
        @if (!$is_category)
        <a href="{{ route('clothes.create') }}" class="card menu-card col-12 mx-auto text-dark text-decoration-none">
            <span class="fw-light text-secondary return-icon text-center mx-auto"><i class="fa-solid fa-rotate-left"></i></span>
            <span class="text-center mx-auto">もどる</span>
        </a>
        @endif
        <button type="button" class="card menu-card col-12 mx-auto" data-bs-toggle="modal" data-bs-target="#menu-create-modal">
            <span class="fw-light text-primary menu-icon text-center mx-auto">＋</span>
            <span class="text-center mx-auto">{{ $is_category ? 'カテゴリー': $category }}を追加する</span>
        </button>
        @foreach ($cards as $card)
        @if ($is_category)
        <button class="card menu-card col-12 mx-auto" wire:click="select({{ $card->id }})">
            <div class="category-label-field position-relative">
                <p class="col-12 category-label text-center position-absolute top-50 start-50 translate-middle">{{ $card->name }}</p>
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
