<template>
    <div class="col-12 px-2 d-flex">
        <div class="col-6 px-4">
            <div class="col-12">
                <customer-info-component
                    :customer="customer"
                ></customer-info-component>
            </div>
            <div class="col-12 mt-2">
                <div class="card col-12 py-2 h4 text-center">
                    未渡し一覧
                </div>
                <div class="card position-relative unhanded-orders">
                    <div class="col-12 mb-2 bg-white position-sticky unhanded-orders-column pt-3 border border-white">
                        <div class="card col-11 mx-auto bg-primary text-white">
                            <div class="d-flex">
                                <div class="col-2 text-center">伝票No.</div>
                                <div class="col-3 text-center">預り日</div>
                                <div class="col-5 text-center">顧客名</div>
                                <div class="col-2 text-center">支払い済</div>
                            </div>
                        </div>
                    </div>
                    @foreach ($orders as $order)
                    <div class="card col-11 mx-auto my-2 py-2 unhanded-orders-order">
                        <div class="d-flex">
                            <div class="col-2 text-center">{{ $order['id'] }}</div>
                            <div class="col-3 text-center">{{ date('Y/m/d', strtotime($order['created_at'])) }}</div>
                            <div class="col-5 text-center">{{ $customer['name'] }}</div>
                            <div class="col-2 text-center"><i class="{{ !is_null($order['paid_at']) ? 'fa-solid fa-check text-success' : 'fa-solid fa-xmark text-danger' }}"></i></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-6 px-4">
            <div class="col-12">
                <div class="card col-12 py-2 h4 text-center">
                    伝票詳細
                </div>
            </div>
            <div class="card col-12 unhanded-operation">
                <div class="card-header text-center fw-bold">
                    操作
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-around">
                        <div class="unhanded-operation-btn text-white mt-2 bg-secondary">
                            <div class="unhanded-operation-btn-icon">
                                <i class="fa-solid fa-house"></i>
                            </div>
                            <div class="unhanded-operation-btn-label text-center">
                                戻る
                            </div>
                        </div>
                        <div class="unhanded-operation-btn text-white mt-2 uh-orange">
                            <div class="unhanded-operation-btn-icon">
                                <i class="fa-solid fa-money-bill"></i>
                            </div>
                            <div class="unhanded-operation-btn-label text-center">
                                入金
                            </div>
                        </div>
                        <div class="unhanded-operation-btn text-white mt-2 uh-green">
                            <div class="unhanded-operation-btn-icon">
                                <i class="fa-regular fa-square-check"></i>
                            </div>
                            <div class="unhanded-operation-btn-label">
                                全選択
                            </div>
                        </div>
                        <div class="unhanded-operation-btn bg-primary text-white mt-2">
                            <div class="unhanded-operation-btn-icon">
                                <i class="fa-solid fa-shirt"></i>
                            </div>
                            <div class="unhanded-operation-btn-label">
                                お渡し
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card position-relative mt-2 unhanded-detail">
                <div class="col-12 mb-2 bg-white position-sticky unhanded-detail-column pt-3 border border-white">
                    <div class="card col-11 mx-auto bg-primary text-white">
                        <div class="d-flex">
                            <div class="col-3 text-center">タグNo.</div>
                            <div class="col-7 text-center">商品名</div>
                            <div class="col-2 text-center">値段</div>
                        </div>
                    </div>
                </div>
                @foreach ($orders as $order)
                <div class="card col-11 mx-auto my-2 py-2 unhanded-detail-clothes unhanded-selected">
                    <div class="d-flex">
                        <div class="col-2 text-center">{{ $order['id'] }}</div>
                        <div class="col-3 text-center">{{ date('Y/m/d', strtotime($order['created_at'])) }}</div>
                        <div class="col-5 text-center">{{ $customer['name'] }}</div>
                        <div class="col-2 text-center"><i class="{{ !is_null($order['paid_at']) ? 'fa-solid fa-check text-success' : 'fa-solid fa-xmark text-danger' }}"></i></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</template>

<script>
import CustomerInfo from './CustomerInfoComponent';
export default ({
    props: {
        customer: {
            required: true
        }
    },
    components: {
        'customer-info-component': CustomerInfo,
    },
})
</script>
