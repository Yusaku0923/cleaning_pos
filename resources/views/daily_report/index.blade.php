@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-12 d-flex border-bottom border-2 mt-2 pb-2 dr-header">
        <div class="col-4 px-3 dr-earnings text-center">
            <p class="dr-earnings-label">日次売上： <span class="border-bottom border-secondary">{{ number_format($daily_sum) }} 円</span></p>
            <p class="dr-earnings-label">月次売上： <span class="border-bottom border-secondary">{{ number_format($monthly_sum) }} 円</span></p>
        </div>
        <div class="col-4 dr-date text-center fw-bold">
            <span>{{ $date === date('Y-m-d') ? '本日': date('Y年m月d日', strtotime($date)) }}の日報</span>
        </div>
        <div class="col-4">
            <daily-report-search-component
                :date="{{ json_encode($date) }}"
            ></daily-report-search-component>
        </div>
    </div>

    <div class="col-12 dr-body px-5">
        <div class="dr-body-column py-4">
            <div class="col-10 d-flex mx-0 dr-body-column-inner bg-primary text-white" >
                <div class="col-2 text-center">伝票番号</div>
                <div class="col-4 text-center">お名前</div>
                <div class="col-2 text-center">合計</div>
                <div class="col-2 text-center">支払い済</div>
                <div class="col-2 text-center">受付時間</div>
            </div>
        </div>
        @foreach ($daily_orders as $order)
        <input type="checkbox" class="dr-body-toggle d-none" id="{{ 'block-'.$order['id'] }}">
        <label for="{{ 'block-'.$order['id'] }}" class="card col-10 mx-0 mt-3 dr-body-cards">
            <div class="col-12 d-flex dr-body-cards-inner">
                <div class="col-2 text-center">{{ $order['id'] }}</div>
                <div class="col-4 text-center">{{ $order['name'] }}</div>
                <div class="col-2 text-center">{{ number_format($order['amount']) }}円</div>
                <div class="col-2 text-center">
                    <i class="{{ !is_null($order['paid_at']) ? 'fa-solid fa-check text-success' : 'fa-solid fa-minus text-secondary' }}"></i>
                </div>
                <div class="col-2 text-center">{{ date('H時i分', strtotime($order['created_at'])) }}</div>
            </div>
        </label>
        <div class="card col-10 mx-0 dr-body-ac">
            <div class="col-12 d-flex justify-content-center fw-bold">
                <div class="col-2 text-center">タグ番号</div>
                <div class="col-4 text-center">商品名</div>
                <div class="col-2 text-center">値段</div>
            </div>
            @foreach ($order['items'] as $item)
            <div class="col-12 d-flex justify-content-center">
                <div class="col-2 text-center">{{ $item['tag'] }}</div>
                <div class="col-4 text-center">{{ $item['name'] }}</div>
                <div class="col-2 text-center">{{ number_format($item['price']).'円' }}</div>
            </div>
            @endforeach

            @if ($order['reduction'] !== 0)
            <div class="col-12 px-5 text-end mt-2">
                <span class="fw-bold">値引き金額</span>：{{ number_format($order['reduction']) }} 円 {{ $order['discount'] !== 0 ? '('. $order['discount'] .'%引き)': '' }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <a class="dr-pdf" href="{{ route('daily_report.generate', $date) }}">
        PDF出力
    </a>
</div>

@endsection

