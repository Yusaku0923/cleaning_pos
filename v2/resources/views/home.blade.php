@extends('layouts.app')

@section('content')
<div class="col-12 px-2">
    <div class="col-12 row justify-content-center mx-auto">
        <div class="col-12 row justify-content-between">
            {{-- Left Block --}}
            <div class="col-6 left-block position-relative">
                <div style="height: 73dvh">
                    <customer-info-component
                        :customer="{{ json_encode($customer) }}"
                        :info="{{ json_encode($customer_info) }}"
                    ></customer-info-component>

                    <div class="card card-border col-12 py-2 mt-4 h4 text-center">
                        顧　客　情　報
                    </div>
                    <div class="card card-border col-12 p-2 text-start">
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
                                    <span class="d-inline-block col-8">{{ isset($unpaid_amount) ? number_format($unpaid_amount) . '円' : '' }}</span>
                                </p>
                            </div>
                            <div class="card col-6 p-1">
                                <p class="mb-0 d-flex">
                                    <span class="d-inline-block col-4">最終来店日</span>
                                    <span class="d-inline-block col-8">{{ $latest_visit ? date('Y/m/d', strtotime($latest_visit)) : '' }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-12 mt-1 d-flex">
                            <div class="col-6">
                                @if (!empty($customer) && (boolean)$customer->is_invoice)
                                <div class="card col-12 p-1">
                                    <p class="mb-0 d-flex">
                                        <span class="d-inline-block col-4">入金管理</span>
                                        @if (isset($customer->needs_payment_confirmation))
                                        <span class="d-inline-block col-8">{{ (boolean)$customer->needs_payment_confirmation ? 'する': 'しない' }}</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="card col-12 p-1 mt-1">
                                    <p class="mb-0 d-flex">
                                        <span class="d-inline-block col-4">締め日</span>
                                        @if (isset($customer->is_invoice) && (boolean)$customer->is_invoice)
                                        <span class="d-inline-block col-8">{{ (int)$customer->cutoff_date === \App\Domain\Billing\InvoicePeriod::MONTH_END_CUTOFF ? '末日': $customer->cutoff_date . '日' }}</span>
                                        @endif
                                    </p>
                                </div>
                                @endif
                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                @if (!empty($customer))
                                    <div class="col-11 mt-3 customer-edit-btn" data-bs-toggle="modal" data-bs-target="#customer-edit-modal">
                                        <i class="fa-solid fa-wrench" style="line-height: 52px;margin-right: 10px;"></i>顧客情報編集
                                    </div>
                                @else
                                    <div class="col-11 mt-3 customer-edit-btn bg-secondary" style="height: 52px;"></div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- <div class="card card-border col-12 fs-20 py-1 px-3 mt-3 {{ session('theme_header') ? session('theme_header') : (session('theme_body') ? session('theme_body') : '') }}">
                        <span class="{{ session('theme_header') ? session('theme_header').'-header' : (session('theme_body') ? session('theme_body').'-header' : '') }}">
                            担当者：<span class="{{ is_null(session('manager_name')) ? 'text-danger fw-bold': '' }}">{{ session('manager_name') ?? '設定されていません' }}</span>
                        </span>
                    </div> --}}
                </div>
                <div class="col-12 d-flex justify-content-between">
                    @if(!empty($latest_order))
                        <div class="card col-3 col-3-custom mr-1 text-center lh-leftbtn cbtn-red fs-20" data-bs-toggle="modal" data-bs-target="#order-cancel-modal">
                            <div class="mx-auto">直前預り<br>取り消し</div>
                        </div>
                    @else
                        <div class="card col-3 col-3-custom mr-1 text-center lh-leftbtn bg-secondary"></div>
                    @endif

                    {{-- <button type="button" class="card col-3 col-3-custom mr-1 text-center lh-leftbtn cbtn-teal fs-20" data-bs-toggle="modal" data-bs-target="#manager-select-modal">
                        <div class="mx-auto">担当者<br>変更</div>
                    </button> --}}
                    <manager-select-btn-component
                        :managers="{{ json_encode($managers) }}"
                        :selected="{{ json_encode('') }}"
                    ></manager-select-btn-component>

                    @if (!is_null(request()->attributes->get('manager')))
                        <div class="card col-3 col-3-custom mr-1 text-center lh-leftbtn cbtn-teal fs-20" data-bs-toggle="modal" data-bs-target="#tag-edit-modal">
                            <div class="mx-auto">タグ番号<br>{{ is_null($tag) ? '0-000': $tag_label }}</div>
                        </div>
                    @else
                        <div class="card col-3 col-3-custom mr-1 text-center lh-leftbtn bg-secondary"></div>
                    @endif
                    <a href="{{ pos_url('menu') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-blue fs-20">メニュー</a>

                    {{-- modals --}}
                    @if (!empty($customer))
                        @include('modals.edit_customer')
                    @endif
                    @if (!empty($latest_order))
                        @includeIf('modals.cancel_order') {{-- Task 8 --}}
                    @endif
                    @if (!is_null($tag))
                        @includeIf('modals.edit_tag') {{-- Task 8 --}}
                    @endif
                    {{-- modals --}}
                </div>
            </div>

            {{-- Right Block --}}
            <div class="col-6 right-block position-relative">
                <div style="height: 73dvh;">
                    <div class="card card-border col-12 py-2 h4 text-center">
                        顧　客　呼　出
                    </div>
                    <div class="card card-border">
                        <div class="col-12 d-flex justify-content-around" style="padding: 14px 0">
                            @if (!is_null(request()->attributes->get('manager')))
                                <a href="{{ pos_url('customer.create') }}" class="card col-5 text-center cbtn cbtn-lg cbtn-blue" style="padding: 2.5rem 0">
                                    新規登録
                                </a>
                            @else
                                <div class="card col-5 py-5 text-center cbtn cbtn-lg bg-secondary" ><div style="height: 30px"></div></div>
                            @endif

                            @if (!is_null(request()->attributes->get('manager')))
                                <a href="{{ pos_url('customer.search') }}" class="card col-5 text-center cbtn cbtn-lg cbtn-green"  style="padding: 2.5rem 0">
                                    顧客検索
                                </a>
                            @else
                                <div class="card col-5 py-5 text-center cbtn cbtn-lg bg-secondary"><div style="height: 30px"></div></div>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-between mt-2" style="padding: 2px 0;">
                        @if (!empty($customer))
                            <a href="{{ pos_url('payment.index') }}" class="card col-15 py-3 mr-1 text-center text-decoration-none ctl-btn cbtn-yellow fs-20 position-relative">
                                入金
                            </a>
                        @else
                            <div class="card col-15 py-3 mr-1 text-center text-decoration-none ctl-btn bg-secondary fs-20 position-relative">
                                <div style="height: 45px"></div>
                            </div>
                        @endif

                        @if (!empty($customer))
                            <a href="{{ pos_url('invoice.payment_confirmation') }}" class="card col-15 py-3 mr-1 text-center text-decoration-none ctl-btn cbtn-green fs-20">
                                入金確認
                            </a>
                        @else
                            <div class="card col-15 py-3 mr-1 text-center text-decoration-none ctl-btn bg-secondary fs-20 position-relative">
                                <div style="height: 45px"></div>
                            </div>
                        @endif

                        @if (!empty($customer))
                            <a href="{{ pos_url('order.show') }}" class="card col-15 py-3 ml-1 text-center text-decoration-none ctl-btn cbtn-green fs-20">一覧</a>
                        @else
                            <div class="card col-15 py-3 mr-1 text-center text-decoration-none ctl-btn bg-secondary fs-20 position-relative">
                                <div style="height: 45px"></div>
                            </div>
                        @endif

                        <div class="card col-15 py-3 mx-1 text-center ctl-btn bg-secondary"></div>

                        @if (!empty($customer))
                            <a href="{{ pos_url('return.index') }}" class="card col-15 py-3 ml-1 text-center text-decoration-none ctl-btn cbtn-blue fs-20">
                                お渡し
                            </a>
                        @else
                            <div class="card col-15 py-3 mr-1 text-center text-decoration-none ctl-btn bg-secondary fs-20 position-relative">
                                <div style="height: 45px"></div>
                            </div>
                        @endif
                    </div>
    
                    <div class="card card-border col-12 py-2 h4 text-center mt-2">
                        来　店　履　歴
                    </div>
                    <div class="card card-border history-field">
                        <div class="col-12 d-flex py-1 justify-content-around">
                            @foreach ($orders as $order)
                            <history-card-component
                                :customer="{{ json_encode($customer) }}"
                                :order="{{ json_encode($order) }}"
                            ></history-card-component>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-between">
                    @if (!empty($customer))
                        <a href="{{ pos_url('customer.clear') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-red fs-20">入力クリア</a>
                    @else
                        <div class="card col-3 col-3-custom lh-rightbtn text-center lh-leftbtn bg-secondary"></div>
                    @endif
                    @if (!is_null(request()->attributes->get('manager')))
                        <a href="{{ pos_url('customer.list') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-teal fs-20">顧客一覧</a>
                    @else
                        <div class="card col-3 col-3-custom lh-rightbtn text-center bg-secondary"></div>
                    @endif
                    <a href="{{ pos_url('order.all_show') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-green fs-20">全体検索</a>
                    @if (!empty($customer))
                        <a href="{{ pos_url('order.create') }}" class="card col-3 col-3-custom lh-rightbtn text-center text-decoration-none cbtn-blue fs-20">預り入力</a>
                    @else
                        <div class="card col-3 col-3-custom lh-rightbtn text-center lh-leftbtn bg-secondary"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
