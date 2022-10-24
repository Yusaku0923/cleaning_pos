@extends('layouts.app')

@section('content')

<div class="col-12 py-3 px-5 d-flex justify-content-between">
    <div class="order-left">
        <div class="bg-primary search-menu">
            
        </div>
    </div>
    <div class="order-right">
        <div class="bg-danger slip-window">
            
        </div>
        <div class="order-btn-area d-flex justify-content-between">
            <div class="col-6 order-btn-left d-flex justify-content-between">
                <button class="card cbtn order-btn text-danger p-4 mb-0">伝票取消</button>
                <button class="card cbtn order-btn p-4 mb-0">一時保存</button>
            </div>
            <div class="col-5 order-btn-right d-flex justify-content-end">
                <button class="card cbtn cbtn-blue order-btn p-4 mb-0">お支払いへ進む</button>
            </div>
        </div>
    </div>
</div>

@endsection
