@extends('layouts.app')

@section('content')

<div class="col-12 py-3 mx-auto d-flex justify-content-between">
    {{-- category bar --}}
    <div class="category-bar col-3">
        @foreach ($categories as $item)
        {{-- element --}}
        <div class="card py-5 px-2 border border-secondary">
            {{ $item->name }}
        </div>
        {{-- element --}}
        @endforeach
    </div>
    {{-- category bar --}}

    {{-- clothes bar --}}
    <div class="clothes-bar col-5">
        @foreach ($categories as $item)
        {{-- element bar --}}
        <div class="card border border-secondary clothes-card position-relative p-2">
            <div class="">
                {{ $item->name }}
            </div>
            <div class="position-absolute bottom-0">
                {{ number_format(1000) }}円
            </div>
        </div>
        {{-- element bar --}}
        @endforeach
    </div>
    {{-- clothes bar --}}

    {{-- slip bar --}}
    <div class="slip-bar col-4 border border-secondary position-relative">
        <div class="col-12 py-3 px-2 border-bottom border-secondary bg-white d-flex justify-content-between bill-header">
           <div class="col-3 text-primary">
                <i class="fa-solid fa-chevron-left"></i> 戻る
           </div>
           <div class="col-6 text-center">
                中山友作様
           </div>
           <div class="col-3"></div>
        </div>

        <div class="bill-detail">
            <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row">
                <div class="col-6 px-3">
                    数量
                </div>
                <div class="col-6 px-3 text-end text-primary">
                    5点
                </div>
            </div>
            <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row">
                <div class="col-6 px-3">
                    小計
                </div>
                <div class="col-6 px-3 text-end text-primary">
                    1,100 円
                </div>
            </div>
            <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row">
                <div class="col-6 px-3">
                    値引・割引
                </div>
                <div class="col-6 px-3 text-end text-primary">
                    0 円
                </div>
            </div>
            <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row">
                <div class="col-8 px-3">
                    値引・割引を追加
                </div>
                <div class="col-2 px-3 text-end">
                    <i class="fa-solid fa-chevron-right text-secondary"></i>
                </div>
            </div>

            <div class="col-12 py-2 d-flex justify-content-between border-top border-bottom border-1 border-secondary bill-row">
                <div class="col-4 px-3" style="line-height: 2.6">
                    合計
                </div>
                <div class="col-8 px-3 text-end bill-total text-primary">
                    1,100 円
                </div>
            </div>
            <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row">
                <div class="col-6 px-3 text-secondary">
                    内消費税10%
                </div>
                <div class="col-6 px-3 text-end text-secondary">
                    (100 円)
                </div>
            </div>
        </div>

        <div class="col-12 py-4 px-2 bg-primary text-white position-absolute bottom-0 text-center">
            預り金入力
        </div>

    </div>
    {{-- slip bar --}}
</div>

@endsection
