@php
    $is_category = is_null($category_id);
@endphp
<div>
    <div class="card col-12 py-3 mb-4 h4 text-center">
        {{ $is_category ? 'カテゴリー': $category }}
    </div>
    <div class="col-12 menu-columns justify-content-between">
        @if (!$is_category)
        <button class="card menu-card col-12 mx-auto" wire:click="back">
            <span class="fw-light text-secondary return-icon text-center mx-auto"><i class="fa-solid fa-rotate-left"></i></span>
            <span class="text-center mx-auto">もどる</span>
        </button>
        @endif
        <button type="button" class="card menu-card col-12 mx-auto" data-bs-toggle="modal" data-bs-target="#menu-create-modal">
            <span class="fw-light text-primary menu-icon text-center mx-auto">＋</span>
            <span class="text-center mx-auto">{{ $is_category ? 'カテゴリー': $category }}を追加する</span>
        </button>
        @foreach ($cards as $card)
        @if ($is_category)
        <button class="card menu-card col-12 mx-auto" wire:click="select({{ $card->id }})">
            <span class="category-label mx-auto">{{ $card->name }}</span>
        </button>
        @else
        <button class="card menu-card col-12 mx-auto">
            <span class="clothes-label mx-auto">{{ $card->name }}</span>
            <span class="price-label mx-auto">{{ number_format($card->price) }}円</span>
        </button>
        @endif
        @endforeach

    </div>
    <!-- modal -->
    @include('modals.create_menu')
    <!-- modal -->
</div>
