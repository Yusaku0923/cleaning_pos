@extends('layouts.app')
@section('content')
<div class="col-12 px-3 mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ $customer->name }} ー 商品管理</h5>
        <a href="{{ route('delivery.index') }}" class="btn btn-outline-secondary btn-sm">納品書管理へ戻る</a>
    </div>

    @if(session('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @forelse($departments as $department)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="card-title mb-0">{{ $department->name }}</h6>
                <a href="{{ route('delivery.products.create', ['customer' => $customer, 'department_id' => $department->id]) }}"
                   class="btn btn-primary btn-sm">＋商品を追加</a>
            </div>
            @if($department->products->isEmpty())
            <p class="text-muted mb-0">商品が登録されていません</p>
            @else
            <table class="table table-sm table-striped mb-0">
                <thead>
                    <tr>
                        <th>商品名</th>
                        <th class="text-end">単価</th>
                        <th class="text-end">税率</th>
                        <th class="text-end">並び順</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($department->products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td class="text-end">{{ number_format($product->unit_price) }}円</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format($product->tax_rate * 100, 1), '0'), '.') }}%</td>
                        <td class="text-end">{{ $product->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('delivery.products.edit', ['customer' => $customer, 'product' => $product]) }}"
                               class="btn btn-outline-primary btn-sm">編集</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
    @empty
    <div class="alert alert-warning">部署が登録されていません。先に部署を登録してください。</div>
    @endforelse
</div>
@endsection
