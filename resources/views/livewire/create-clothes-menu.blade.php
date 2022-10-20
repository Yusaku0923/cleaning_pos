<div class="col-12 menu-columns justify-content-between">
    <div class="card menu-card col-12 mx-auto">
        <span class="fw-light text-primary menu-icon text-center">＋</span>
        <span class="text-center">カテゴリーを追加する</span>
    </div>
    @foreach ($cards as $card)
        @if (is_null($category_id))
            <button class="card menu-card col-12 mx-auto" wire:click="select({{ $card->id }})">
                <span class="category-label mx-auto">{{ $card->category }}</span>
            </button>
        @else
            <button class="card menu-card col-12 mx-auto">
                <span class="clothes-label">{{ $card->name }}</span>
                <span class="price-label">{{ number_format($card->price) }}円</span>
            </button>
        @endif
    @endforeach
</div>
