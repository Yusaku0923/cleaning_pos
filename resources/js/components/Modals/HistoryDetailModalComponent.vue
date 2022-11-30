<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window border border-4 border-dark">
                <div class="modal-content">
                    <div class="card modal-cd">
                        <div class="card-header col-12 modal-cd-label">伝票詳細</div>
                        <div class="card-body col-12 modal-cd-detail fs-24 position-relative">
                            <div style="height: 38vh;" v-if="!openAC">
                                <div class="col-12 d-flex my-1" >
                                    <div class="col-4 fw-bold">伝票番号</div>
                                    <div class="col-8">{{ order.id }}</div>
                                </div>
                                <div class="col-12 d-flex my-1">
                                    <div class="col-4 fw-bold">お預り日時</div>
                                    <div class="col-8">{{ dateFormater(order.created_at, 'YYYY年MM月DD日 HH時mm分') }}</div>
                                </div>
                                <div class="col-12 d-flex my-1">
                                    <div class="col-4 fw-bold">合計金額</div>
                                    <div class="col-8">{{ order.amount.toLocaleString() }}円</div>
                                </div>
                                <div class="col-12 d-flex my-1">
                                    <div class="col-4 fw-bold">割引金額</div>
                                    <div class="col-8" v-if="order.reduction !== 0">
                                        {{ order.reduction.toLocaleString() }}円
                                        <span v-if="order.discount !== 0">({{ order.discount }}%引き)</span>
                                    </div>
                                    <div class="col-8" v-else><i class="fa-solid fa-minus"></i></div>
                                </div>
                                <div class="col-12 d-flex my-1">
                                    <div class="col-4 fw-bold">お支払方法</div>
                                    <div class="col-8 " v-if="order.invoice_id !== null"><span class="modal-cd-detail-invoice">請求書払い</span></div>
                                    <div class="col-8 " v-else-if="isPaidLater()"><span class="modal-cd-detail-later">後払い</span></div>
                                    <div class="col-8 " v-else><span class="modal-cd-detail-cash">現金払い</span></div>
                                </div>
                                <div class="col-12 d-flex my-1">
                                    <div class="col-4 fw-bold">お支払済み</div>
                                    <div class="col-8" v-if="order.paid_at !== null"><i class="fa-solid fa-check text-success"></i></div>
                                    <div class="col-8" v-else-if="isWaiting4PayCheck()"><span class="modal-cd-detail-pycheck">入金確認待ち</span></div>
                                    <div class="col-8" v-else><i class="fa-solid fa-xmark text-danger"></i></div>
                                </div>
                                <div class="col-12 d-flex my-1" v-if="order.invoice_id === null && order.payment !== 0">
                                    <div class="col-4 fw-bold">お支払い金額</div>
                                    <div class="col-8 ">{{ order.payment.toLocaleString() }}円</div>
                                </div>
                                <div class="col-12 d-flex my-1">
                                    <div class="col-4 fw-bold">お渡し済み</div>
                                    <div class="col-8" v-if="order.handed_at !== null"><i class="fa-solid fa-check text-success"></i></div>
                                    <div class="col-8" v-else><i class="fa-solid fa-xmark text-danger"></i></div>
                                </div>
                                <div class="col-12 d-flex my-1" v-if="order.handed_at !== null">
                                    <div class="col-4 fw-bold">お渡し日時</div>
                                    <div class="col-8">{{ dateFormater(order.handed_at, 'YYYY年MM月DD日 HH時mm分') }}</div>
                                </div>
                            </div>

                            <div class="col-12 position-absolute modal-cd-bottoms">
                                <div class="col-12 d-flex justify-content-between px-3">
                                    <div class="col-8 fw-bold" style="line-height: 46px;">預り一覧（全{{ order.count }}点）</div>
                                    <div class="col-4 text-end px-3" v-if="!openAC">
                                        <button class="px-2 border border-3 border-dark modal-cd-btn modal-cd-btn-toggle-o" @click="toggleAC()"><i class="fa-solid fa-angle-up pe-2"></i>開く</button>
                                    </div>
                                    <div class="col-4 text-end px-3" v-else>
                                        <button class="px-2 border border-3 border-dark modal-cd-btn modal-cd-btn-toggle-c" @click="toggleAC()"><i class="fa-solid fa-angle-down pe-2"></i>閉じる</button>
                                    </div>
                                </div>
                                <div class="col-12 mt-2 position-relative card modal-cd-list fs-18"
                                    :class="{ 'modal-cd-list-open': openAC }">
                                    <div class="col-12 mb-2 position-sticky modal-cd-list-column pt-3">
                                        <div class="card col-11 mx-auto modal-cd-list-column-bar">
                                            <div class="d-flex">
                                                <div class="col-3 text-center">タグNo.</div>
                                                <div class="col-5 text-center">商品名</div>
                                                <div class="col-2 text-center">値段</div>
                                                <div class="col-2 text-center">渡し</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card col-11 mx-auto my-1 py-2"
                                        v-for="item in order.items"
                                        :key="item.id">
                                        <div class="d-flex">
                                            <div class="col-3 text-center">{{ item.tag }}</div>
                                            <div class="col-5 text-center">{{ item.name }}</div>
                                            <div class="col-2 text-center">{{ item.price.toLocaleString() }}円</div>
                                            <div class="col-2 text-center">
                                                <i class="fa-solid fa-check text-success" v-if="item.handed_at !== null"></i>
                                                <i class="fa-solid fa-xmark text-danger" v-else></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 card-footer py-3 d-flex justify-content-end">
                            <div class="col-3 text-end">
                                <button class="px-3 py-1 modal-cd-btn modal-cd-btn-back" @click="$emit('close')">閉じる</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import moment from "moment";

export default ({
    props: {
        customer: {
            required: true
        },
        order: {
            required: true
        }
    },
    data() {
        return {
            openAC: false,
            hideUpper: false
        }
    },
    methods: {
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
        },
        isPaidLater: function() {
            if (this.order.invoice_id === null && this.order.payment === 0 && this.order.paid_at === null) return true;
            // 後払いの定義としては (支払い日時) - (預り日時) > 60min とする
            let dateReceived = moment(this.order.created_at);
            let datePaid = moment(this.order.paid_at);
            if (datePaid.diff(dateReceived) / (1000 * 60) > 60) {
                return true;
            } else {
                return false;
            }
        },
        isWaiting4PayCheck: function () {
            if (Boolean(this.customer.needs_payment_confimation) && this.order.invoice_id !== null && this.order.paid_at === null) {
                return true;
            } else {
                return false;
            }
        },
        toggleAC: function() {
            this.openAC = !this.openAC;
        }
    }
});
</script>
