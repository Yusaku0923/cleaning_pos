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
                    <div class="card col-12 p-1">
                        <p class="mb-0 d-flex">
                            <span class="d-inline-block col-2">名前</span>
                            <span class="d-inline-block col-5">{{ isset($customer->name) ? $customer->name . '　様': (isset($customer->name_kana) ? $customer->name_kana . '　様': '') }}</span>
                            <span class="d-inline-block col-5">{{ isset($customer->name_kana) ? '(　　' . $customer->name_kana . '　　)': '' }}</span>
                        </p>
                    </div>
                    <div class="col-12 mt-1 d-flex justify-content-between">
                        <div class="card col-12 p-1">
                            <p class="mb-0 d-flex">
                                <span class="d-inline-block col-2">電話番号</span>
                                <span class="d-inline-block col-10">{{ isset($customer->phone_number) ? $customer->phone_number: '' }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="col-12 mt-1 d-flex justify-content-between">
                        <div class="card col-6 p-1">
                            <p class="mb-0 d-flex">
                                <span class="d-inline-block col-4">累計売上</span>
                                <span class="d-inline-block col-8">{{ isset($customer->total_sales) ? number_format($customer->total_sales) . '円': '' }}</span>
                            </p>
                        </div>
                        <div class="card col-6 p-1">
                            <p class="mb-0 d-flex">
                                <span class="d-inline-block col-4">来店回数</span>
                                <span class="d-inline-block col-8">{{ isset($customer->number_of_visits) ? number_format($customer->number_of_visits) . '回': '' }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="col-12 mt-1 d-flex justify-content-between">
                        <div class="card col-6 p-1">
                            <p class="mb-0 d-flex">
                                <span class="d-inline-block col-4">未収金</span>
                                <span class="d-inline-block col-8"></span>
                            </p>
                        </div>
                        <div class="card col-6 p-1">
                            <p class="mb-0 d-flex">
                                <span class="d-inline-block col-4">最終来店日</span>
                                <span class="d-inline-block col-8"></span>
                            </p>
                        </div>
                    </div>
                    <div class="col-12 mt-1 d-flex">
                        <div class="col-6">
                            @if ((boolean)$customer->is_invoice)
                            <div class="card col-12 p-1">
                                <p class="mb-0 d-flex">
                                    <span class="d-inline-block col-4">入金管理</span>
                                    @if (isset($customer->needs_payment_confimation))
                                    <span class="d-inline-block col-8">{{ (boolean)$customer->needs_payment_confimation ? 'する': 'しない' }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="card col-12 p-1 mt-1">
                                <p class="mb-0 d-flex">
                                    <span class="d-inline-block col-4">締め日</span>
                                    @if (isset($customer->is_invoice) && (boolean)$customer->is_invoice)
                                    <span class="d-inline-block col-8">{{ $customer->cutoff_date === 99 ? '末日': $customer->cutoff_date . '日' }}</span>
                                    @endif
                                </p>
                            </div>
                            @endif
                        </div>
                        <div class="col-6 d-flex justify-content-end">
                            <div class="col-11 mt-3 customer-edit-btn" data-bs-toggle="modal" data-bs-target="#customer-edit-modal"><i class="fa-solid fa-wrench" style="line-height: 52px;margin-right: 10px;"></i>顧客情報編集</div>
                        </div>
                    </div>
                </div>

                <div class="card col-12 py-1 px-3 mt-3"><span>担当者：<span class="{{ is_null(session('manager_name')) ? 'text-danger fw-bold': '' }}">{{ session('manager_name') ?? '設定されていません' }}</span></span></div>
                <div class="col-12 d-flex justify-content-between" style="margin-top: 20px">
                    <div class="card col-15 mr-1 text-center lh-leftbtn cbtn-red" data-bs-toggle="modal" data-bs-target="#order-cancel-modal">直前預り<br>取り消し</div>
                    <button type="button" class="card col-15 mr-1 text-center lh-leftbtn cbtn-blue" data-bs-toggle="modal" data-bs-target="#manager-select-modal"><div class="mx-auto">担当者<br>変更</div></button>
                    <div class="card col-15 mr-1 text-center lh-leftbtn cbtn-blue" data-bs-toggle="modal" data-bs-target="#tag-edit-modal">タグ番号<br>{{ is_null($tag) ? '0-000': Utility::convertTagFormat($tag) }}</div>
                    <a href="{{ route('daily_report.index') }}" class="card col-15 mr-1 text-center lh-leftbtn-oneline text-decoration-none cbtn-blue">日報</a>
                    <a href="{{ route('invoice.index') }}" class="card col-15 mr-1 text-center lh-leftbtn-oneline text-decoration-none cbtn-blue">請求書</a>

                    {{-- modals --}}
                    @if (!empty($customer))
                        @include('modals.edit_customer')
                    @endif
                    @if (!empty($latest_order))
                        @include('modals.cancel_order')
                    @endif
                    @include('modals.select_manager')
                    @if (!is_null($tag))
                        @include('modals.edit_tag')
                    @endif
                    {{-- modals --}}
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
                    <a href="{{ route('payment.index') }}" class="card col-15 py-3 mr-1 text-center text-decoration-none ctl-btn cbtn-blue">入金</a>
                    <div class="card col-15 py-3 mx-1 text-center ctl-btn">出金</div>
                    <a href="{{ route('order.show') }}" class="card col-15 py-3 ml-1 text-center text-decoration-none ctl-btn cbtn-blue">預かり一覧</a>
                    <div class="card col-15 py-3 mx-1 text-center ctl-btn">納品検索</div>
                    <a href="{{ route('return.index') }}" class="card col-15 py-3 ml-1 text-center text-decoration-none ctl-btn cbtn-blue">お渡し</a>
                </div>

                <div class="card col-12 py-2 h4 text-center mt-2">
                    来　店　履　歴
                </div>
                <div class="card history-field">
                    <div class="col-12 d-flex py-2 justify-content-around">
                        @foreach ($orders as $order)
                        <history-card-component
                            :customer="{{ json_encode($customer) }}"
                            :order="{{ json_encode($order) }}"
                        ></history-card-component>
                        @endforeach
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-between" style="margin-top: 20px;">
                    <a href="{{ route('menu') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-blue">メニュー</a>
                    <div class="card col-3 col-3-custom lh-rightbtn text-center bg-secondary"></div>
                    <a href="{{ route('customer.clear') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-red">入力クリア</a>
                    <a href="{{ route('order.create') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-blue">預り入力</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
