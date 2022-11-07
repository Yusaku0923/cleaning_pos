<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close', 2)">
            <div class="modal-window">
                <div class="modal-content">
                    <!-- <div class="menu-modal-close">
                        <i class='fa-solid fa-xmark' @click="$emit('close')"></i>
                    </div> -->
                    <div class="modal-ac-board">
                        <div class="col-11 modal-ac-header mx-auto text-center text-white border-bottom border-white">預り金入力</div>
                        <div class="col-12 modal-ac-body d-flex justify-content-between">
                            <div class="col-5 p-3">
                                <div class="col-12 modal-ac-result">
                                    <div class="col-12 modal-ac-result-total border-bottom border-secondary d-flex justify-content-between p-2">
                                        <div class="modal-ac-result-total-label fw-bold">合計</div>
                                        <div class="modal-ac-result-total-price position-relative py-2 fw-bold">
                                            {{ amount.toLocaleString() }} 円
                                            <p class="text-danger mb-0 position-absolute end-0 fw-normal" v-show="payment < 0"><span style="font-size: 14px;">不足</span> {{ (payment - amount).toLocaleString() }} 円</p>
                                        </div>
                                    </div>

                                    <div class="col-12 modal-ac-result-change d-flex justify-content-between p-2">
                                        <div class="modal-ac-result-change-label fw-bold">お釣り</div>
                                        <div class="modal-ac-result-change-price fw-bold" v-if="payment < 0">
                                            0 円
                                        </div>
                                        <div class="modal-ac-result-change-price fw-bold" v-else>
                                            {{ (payment - amount).toLocaleString() }} 円
                                        </div>
                                    </div>

                                    <div class="col-12 modal-ac-result-receive d-flex justify-content-between p-2">
                                        <div class="modal-ac-result-receive-label fw-bold">
                                            預り金額
                                        </div>
                                        <div class="modal-ac-result-receive-price fw-bold">
                                            {{ payment.toLocaleString() }} 円
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-7 p-3">
                                <div class="modal-ac-keypad position-relative mx-auto text-primary">
                                    <div class="modal-ac-keypad-upper">
                                        <div class="modal-ac-keypad-upper-key" @click="typeNumber('7')">7</div>
                                        <div class="modal-ac-keypad-upper-key" @click="typeNumber('8')">8</div>
                                        <div class="modal-ac-keypad-upper-key" @click="typeNumber('9')">9</div>
                                        <div class="modal-ac-keypad-upper-backspace text-white border border-bottom-0 border-white" @click="typeBackspace()"><i class="fa-solid fa-delete-left"></i></div>
                                        <div class="modal-ac-keypad-upper-key" @click="typeNumber('4')">4</div>
                                        <div class="modal-ac-keypad-upper-key" @click="typeNumber('5')">5</div>
                                        <div class="modal-ac-keypad-upper-key" @click="typeNumber('6')">6</div>
                                        <div class="modal-ac-keypad-upper-minus text-white border border-bottom-0 border-white"><i class="fa-solid fa-minus"></i></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <div class="modal-ac-keypad-lower">
                                            <div class="modal-ac-keypad-lower-key" @click="typeNumber('1')">1</div>
                                            <div class="modal-ac-keypad-lower-key" @click="typeNumber('2')">2</div>
                                            <div class="modal-ac-keypad-lower-key" @click="typeNumber('3')">3</div>
                                            <div class="modal-ac-keypad-lower-key" @click="typeZero(10)">0</div>
                                            <div class="modal-ac-keypad-lower-key" @click="typeZero(100)">00</div>
                                            <div class="modal-ac-keypad-lower-key" @click="typeZero(1000)">000</div>
                                        </div>
                                        <div class="modal-ac-keypad-lower-just text-white border border-white" @click="typeJust()"><i class="fa-solid fa-arrow-down"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TODO:請求書払いフラグ -->

                        <div class="col-12 modal-ac-send bg-primary text-center text-white border border-white"
                            @click="$emit('account', payment)"
                            :class="{ 'bg-primary': payment >= amount, 'bg-secondary': payment < amount }"
                        >
                            お会計
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
<script>
export default {
    name: "discount",
    props: {
        amount: {
            type: Number,
            required: true,
        }
    },
    data() {
        return {
            payment: 0
        }
    },
    methods: {
        typeNumber: function(num) {
            if (this.payment === 0) {
                this.payment = Number(num);
            } else {
                let payment = Number(this.payment + num);
                if (payment < 1000000) {
                    this.payment = payment;
                }
            }
        },
        typeZero: function(multi) {
            let payment = this.payment * multi;
            if (payment < 1000000) {
                this.payment = payment;
            }
        },
        typeBackspace: function() {
            this.payment = Number(String(this.payment).slice(0, -1));
        },
        typeJust: function() {
            this.payment = this.amount;
        }
    }
}
</script>