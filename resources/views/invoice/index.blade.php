@extends('layouts.app')

@section('content')

<invoice-component
    :manager_id="{{ json_encode(session()->get('manager_id')) }}"
    :invoices="{{ json_encode($invoices) }}"
    :token="{{ json_encode($token) }}"
    :theme="{{ session('theme_header') ? json_encode(session('theme_header').'-header'): (session('theme_body') ? json_encode(session('theme_header').'-header'): '') }}"
></invoice-component>
{{-- <div class="col-12">
    <div class="col-12 d-flex border-bottom border-2 mt-2 pb-2 dr-header">
        <div class="col-4 iv-title text-center fw-bold mx-auto">請求書発行</div>
    </div>
    <div class="col-12 d-flex">
        <div class="col-8 px-1 iv-left">
            <div class="col-12 iv-left-search">
                <div class="card mt-2">
                    <div class="card-body col-12 p-1 iv-left-search-field d-flex">
                        <div class="col-2">
                            <div class="col-10 bg-secondary text-white rounded text-center">表示項目</div>
                        </div>
                        <div class="col-2">締日：末日</div>
                        <div class="col-3">対象月：2022/09</div>
                        <div class="col-5">名前：なし</div>
                    </div>
                    <div class="card-footer p-1 px-3">
                        <p class="col-12 text-end mb-0">検索項目入力　<i class="fa-solid fa-chevron-down"></i></p>
                    </div>
                </div>
            </div>
            <div class="col-12 iv-left-result mt-2">
                <div class="card iv-left-result-field">
                    <div class="col-11 d-flex mx-auto mt-2 dr-body-column-inner bg-primary text-white" >
                        <div class="col-3 text-center">請求対象期間</div>
                        <div class="col-5 text-center">お名前</div>
                        <div class="col-4 text-center">合計（繰越金）</div>
                    </div>
                    <div class="col-12 overflow-scroll iv-left-result-field-list">
                        <div class="col-11 card mx-auto mt-2 py-2 iv-left-result-field-list-card">
                            <div class="col-12 d-flex">
                                <div class="col-3 text-center">
                                    09/01 ～ 09/30
                                </div>
                                <div class="col-5 text-center">
                                    テストテストテスト
                                </div>
                                <div class="col-4 text-center">
                                    1,800円（3,600円）
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-4 px-3 iv-right">
            <div class="col-12 iv-right-select">
                <div class="card col-12 mx-auto mt-2 p-1 text-center iv-right-select-label">
                    選択中
                </div>
                <div class="card col-12 overflow-scroll mx-auto mt-2 p-2 iv-right-select-field">
                    <div class="card col-12 my-1 py-2 iv-right-select-field-card">
                        <div class="col-12 d-flex">
                            <div class="col-5 text-center">
                                09/01 ～ 09/30
                            </div>
                            <div class="col-7 text-center">
                                テストテストテスト
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a class="iv-reset">
        全選択
    </a>
    <a class="iv-pdf">
        PDF出力
    </a>
</div> --}}

@endsection
