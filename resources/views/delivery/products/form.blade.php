@extends('layouts.app')
@section('content')
@php
    $isEdit = $product->exists;
    $selectedDept = old('delivery_department_id', $product->delivery_department_id);
    $selectedTax = old('tax_rate', $product->exists ? number_format($product->tax_rate, 2, '.', '') : '0.10');
@endphp
<div class="col-12 col-md-8 col-lg-6 mx-auto px-3 mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $customer->name }} ー 商品{{ $isEdit ? '編集' : '追加' }}</h5>
        <a href="{{ route('delivery.products.index', $customer) }}" class="btn btn-outline-secondary btn-sm">一覧へ戻る</a>
    </div>

    <form method="POST"
          action="{{ $isEdit
            ? route('delivery.products.update', ['customer' => $customer, 'product' => $product])
            : route('delivery.products.store', $customer) }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">部署</label>
            <select name="delivery_department_id" class="form-select @error('delivery_department_id') is-invalid @enderror">
                <option value="">選択してください</option>
                @foreach($departments as $department)
                <option value="{{ $department->id }}" {{ (string)$selectedDept === (string)$department->id ? 'selected' : '' }}>
                    {{ $department->name }}
                </option>
                @endforeach
            </select>
            @error('delivery_department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label">商品名</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}"
                   class="form-control @error('name') is-invalid @enderror">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label">単価（円）</label>
            <input type="number" name="unit_price" value="{{ old('unit_price', $product->unit_price) }}" min="0"
                   class="form-control @error('unit_price') is-invalid @enderror">
            @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label">税率</label>
            <select name="tax_rate" class="form-select @error('tax_rate') is-invalid @enderror">
                <option value="0.10" {{ $selectedTax === '0.10' ? 'selected' : '' }}>10%</option>
                <option value="0.08" {{ $selectedTax === '0.08' ? 'selected' : '' }}>8%（軽減税率）</option>
            </select>
            @error('tax_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label">並び順（任意）</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order) }}"
                   class="form-control @error('sort_order') is-invalid @enderror" placeholder="未入力なら自動採番">
            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary">{{ $isEdit ? '更新する' : '追加する' }}</button>
    </form>
</div>
@endsection
