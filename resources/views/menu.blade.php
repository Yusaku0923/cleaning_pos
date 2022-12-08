@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-12 d-flex justify-content-around mx-auto mb-3">
        <a href="{{ route('daily_report.index') }}" class="card col-5 fs-26 p-4 cbtn cbtn-purple text-center">日報</a>
        <a href="{{ route('invoice.index') }}" class="card col-5 fs-26 p-4 cbtn cbtn-purple text-center">請求書</a>
    </div>
    <div class="col-12 d-flex justify-content-around mx-auto">
        <a class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">担当者マスタ</a>
        <a href="{{ route('clothes.create') }}" class="card col-5 fs-26 p-4 cbtn cbtn-blue text-center">商品マスタ</a>
    </div>
</div>
@endsection
