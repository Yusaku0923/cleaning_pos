@extends('layouts.app')

@section('content')

<div class="col-12 py-3 mx-auto d-flex justify-content-between">
    {{-- category bar --}}
    <div class="category-bar col-3">
        @foreach ($category as $item)
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
        @foreach ($category as $item)
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
    <div class="slip-bar col-4 bg-secondary position-relative">
        <div class="col-12 py-3 px-2 border border-secondary bg-white">
            計：0点
        </div>

        {{-- order list --}}
        <div class="order-list">
            @for ($i = 0; $i < 15; $i++)
            {{-- selected card --}}
            <div class="col-12 py-3 px-2 border border-secondary bg-white">
                <div class="col-12 px-2 order-title">絨毯</div>
                <div class="col-12 mt-2 d-flex justify-content-between order-detail">
                    <div class="col-5 d-flex justify-content-between">
                        <button class="card border border-primary text-primary p-2">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <button class="card border border-primary text-primary p-2">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                        <div class="text-primary order-text">
                            1 点
                        </div>
                    </div>
                    <div class="col-5 order-text text-end">
                        1,000円
                    </div>
                </div>
            </div>
            {{-- selected card --}}
            @endfor
        </div>
        {{-- order list --}}

        {{-- OK button --}}
        <div class="col-12 py-4 px-2 bg-primary position-absolute bottom-0">
            5000円 お会計へ
        </div>
        {{-- OK button --}}

    </div>
    {{-- slip bar --}}
</div>

@endsection
