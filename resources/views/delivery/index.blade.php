@extends('layouts.app')
@section('content')
<div class="col-12 px-3 mt-2">
    <h5 class="mb-3">納品書管理</h5>
    @foreach($customers as $customer)
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title">{{ $customer->name }}</h6>
            <a href="{{ route('delivery.notes', $customer) }}" class="btn btn-primary">
                集計・PDF出力
            </a>
            <a href="{{ route('delivery.products.index', $customer) }}" class="btn btn-outline-secondary">
                商品管理
            </a>
        </div>
    </div>
    @endforeach
    <a href="{{ route('delivery.email_logs') }}" class="btn btn-outline-secondary mt-2">送信履歴</a>
</div>
@endsection
