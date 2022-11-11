@extends('layouts.app')

@section('content')
<div class="col-12 px-2">
    <div class="col-12 row justify-content-center mx-auto">
        <div class="col-12 row justify-content-between">
            {{-- Left Block --}}
            <div class="col-6 left-block">
                <div class="card col-12 py-2 h4 text-center">
                    {{ isset($customer->name) ? $customer->name . '　様': '顧客情報が選択されていません' }}
                </div>
                <div class="card col-12 text-center">
                    <table class="table h-100 csinfo-table">
                        <thead>
                            <tr>
                                <th>CS情報</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- <li>でもいいかも --}}
                            <tr><td>Line1</td></tr>
                            <tr><td>Line2</td></tr>
                            <tr><td>Line3</td></tr>
                            <tr><td>Line4</td></tr>
                            <tr><td>Line5</td></tr>
                            <tr><td>Line6</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="card col-12 py-2 mt-2 h4 text-center">
                    顧　客　情　報
                </div>
                <div class="card col-12 p-2 text-start">
                    <div class="card col-12 p-1">名前　{{ isset($customer->name) ? $customer->name . '　様': '' }}</div>
                    <div class="col-12 mt-1 d-flex justify-content-between">
                        <div class="card col-10 p-1">電話番号　{{ isset($customer->phone_number) ? $customer->phone_number: '' }}</div>
                        <div class="card col-2 p-1 text-center">会員客</div>
                    </div>
                    <div class="col-12 mt-1 d-flex justify-content-between">
                        <div class="card col-6 p-1">誕生日　{{ isset($customer->birth_day) ? date('m月d日', strtotime($customer->birth_day)): '' }}</div>
                        <div class="card col-6 p-1">性別　</div>
                    </div>
                    <div class="col-12 mt-1 d-flex justify-content-between">
                        <div class="card col-6 p-1">累計売上　</div>
                        <div class="card col-6 p-1">来店回数　</div>
                    </div>
                    <div class="col-12 mt-1 d-flex justify-content-between">
                        <div class="card col-6 p-1">ポイント　</div>
                        <div class="card col-6 p-1">未収金　</div>
                    </div>
                    <div class="col-12 mt-1 d-flex justify-content-between">
                        <div class="col-7">
                            <div class="card col-12 p-1">
                                最終来店日
                            </div>
                            <div class="card col-12 p-1 mt-1">
                                会員有効期限
                            </div>
                        </div>
                        <div class="card col-5  align-middle">会員情報入力</div>
                    </div>
                </div>

                <div class="card col-12 py-1 px-3 mt-3"><span>担当者：<span class="{{ is_null(session('manager_name')) ? 'text-danger fw-bold': '' }}">{{ session('manager_name') ?? '設定されていません' }}</span></span></div>
                <div class="col-12 d-flex justify-content-between" style="margin-top: 20px">
                    <div class="card col-15 mr-1 text-center lh-leftbtn">直前預り<br>取り消し</div>
                    <button type="button" class="card col-15 mr-1 text-center lh-leftbtn cbtn-blue" data-bs-toggle="modal" data-bs-target="#manager-select-modal"><div class="mx-auto">担当者<br>変更</div></button>
                    <div class="card col-15 mr-1 text-center lh-leftbtn">タグ番号<br>0-000</div>
                    <a href="{{ route('daily_report.index') }}" class="card col-15 mr-1 text-center lh-leftbtn-oneline text-decoration-none text-white bg-primary">日報</a>
                    <div class="card col-15 mr-1 text-center lh-leftbtn-oneline">請求書</div>
                    @include('modals.select_manager')
                </div>
            </div>

            {{-- Right Block --}}
            <div class="col-6 right-block">
                <div class="card col-12 py-2 h4 text-center">
                    顧　客　呼　出
                </div>
                <div class="card">
                    <div class="col-12 d-flex py-4 justify-content-around">
                        <a href="{{ route('customer.create') }}" class="card col-5 py-5 text-center cbtn cbtn-lg cbtn-blue">
                            新規登録
                        </a>
                        <a href="{{ route('customer.search') }}" class="card col-5 py-5 text-center cbtn cbtn-lg cbtn-green">
                            顧客検索
                        </a>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-between mt-2">
                    <div class="card col-15 py-3 mr-1 text-center ctl-btn">入金</div>
                    <div class="card col-15 py-3 mx-1 text-center ctl-btn">出金</div>
                    <div class="card col-15 py-3 mx-1 text-center">預かり一覧<br>(未収・未定)</div>
                    <div class="card col-15 py-3 mx-1 text-center ctl-btn">納品検索</div>
                    <a href="{{ route('return.index') }}" class="card col-15 py-3 ml-1 text-center ctl-btn">お渡し</a>
                </div>

                <div class="card col-12 py-2 h4 text-center mt-2">
                    来　店　履　歴
                </div>
                <div class="card {{ empty($orders) ? 'history-field' : '' }}">
                    <div class="col-12 d-flex py-2 justify-content-around">
                        @foreach ((array_splice($orders, 0, 4)) as $order)
                        <div class="card col-3 col-3-custom p-2 history-card">
                            @foreach (array_splice($order['items'], 0, 2) as $item)
                                <div class="col-12 text-nowrap overflow-hidden">{{ $item['name'] }}</div>
                            @endforeach
                            全{{ $order['total_count'] }}
                            <br>
                            {{ number_format($order['amount']) }}円
                            <br>
                            {{ is_null($order['handed_at']) ? '未渡し': 'お渡し済' }}
                        </div>
                        @endforeach
                    </div>
                    @if (isset($orders))
                    <div class="card col-11 p-2 mx-auto mb-2 text-end">一覧で見る >></div>
                    @endif
                </div>

                <div class="col-12 d-flex justify-content-between" style="margin-top: 20px;">
                    <a href="{{ route('menu') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-blue">メニュー</a>
                    <div class="card col-3 col-3-custom lh-rightbtn text-center">両替</div>
                    <a href="{{ route('customer.clear') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-red">入力クリア</a>
                    <a href="{{ route('order.create') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-blue">預り入力</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
