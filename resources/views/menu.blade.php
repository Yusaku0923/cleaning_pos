@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <a href="{{ route('daily_report.index') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">日報</a>
        <a href="{{ route('invoice.index') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">請求書</a>
    </div>
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <a class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">担当者マスタ</a>
        <a href="{{ route('clothes.create') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">商品マスタ</a>
    </div>
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <a href="{{ route('receipt.edit') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">レシート設定</a>
        <div class="col-5 fs-26 p-4 text-center"></div>
    </div>
</div>
@endsection
