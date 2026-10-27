@extends('layouts.app')
@section('content')
<div class="col-12 px-3 mt-2">
    <h5 class="mb-3">納品書管理</h5>
    @foreach($customers as $customer)
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title">{{ $customer->name }}</h6>
            <a href="{{ pos_url('delivery.notes', ['customer' => $customer->id]) }}" class="btn btn-primary">
                集計・PDF出力
            </a>
            <a href="{{ pos_url('delivery.products.index', ['customer' => $customer->id]) }}" class="btn btn-outline-secondary">
                商品管理
            </a>
        </div>
    </div>
    @endforeach
    <a href="{{ pos_url('delivery.email_logs') }}" class="btn btn-outline-secondary mt-2">送信履歴</a>
</div>
@endsection
