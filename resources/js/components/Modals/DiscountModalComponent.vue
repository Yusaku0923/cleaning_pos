<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close', 2)">
            <div class="modal-window">
                <div class="modal-content">
                    <!-- <div class="menu-modal-close">
                        <i class='fa-solid fa-xmark' @click="$emit('close')"></i>
                    </div> -->
                    <div class="modal-ac-board">
                        <div class="col-11 modal-ac-header mx-auto text-center text-white border-bottom border-white">割引・値引入力</div>
                        <div class="col-12 modal-ac-body d-flex justify-content-between">
                            <div class="col-5 p-3">
                                <div class="col-12 modal-ac-result">
                                    <div class="col-12 modal-ac-result-total border-bottom border-secondary d-flex justify-content-between p-2">
                                        <div class="modal-ac-result-total-label fw-bold" v-if="mode === 'discount'">割引後合計</div>
                                        <div class="modal-ac-result-total-label fw-bold" v-if="mode === 'reduction'">値引後合計</div>
                                        <div class="modal-ac-result-total-price position-relative py-2 fw-bold">
                                            {{ (amount - reduction).toLocaleString() }} 円
                                        </div>
                                    </div>

                                    <div class="col-12 modal-ac-result-change d-flex justify-content-between p-2">
                                        <div class="modal-ac-result-change-label fw-bold" v-if="mode === 'discount'">割引金額</div>
                                        <div class="modal-ac-result-change-label fw-bold" v-if="mode === 'reduction'">値引金額</div>
                                        <div class="modal-ac-result-change-price fw-bold">
                                            {{ reduction.toLocaleString() }} 円
                                        </div>
                                    </div>

                                    <div class="col-12 modal-ac-result-receive d-flex justify-content-between p-2">
                                        <div class="modal-ac-result-receive-label fw-bold" v-if="mode === 'discount'">割引割合</div>
                                        <div class="modal-ac-result-receive-price fw-bold" v-if="mode === 'discount'">
                                            {{ discount.toLocaleString() }} %
                                        </div>

                                        <div class="modal-ac-result-receive-label fw-bold" v-if="mode === 'reduction'">値引金額</div>
                                        <div class="modal-ac-result-receive-price fw-bold" v-if="mode === 'reduction'">
                                            {{ reduction.toLocaleString() }} 円
                                        </div>
                                    </div>

                                    <div class="col-12 mt-5 d-flex justify-content-between text-white">
                                        <div class="modal-dis-btn text-center  border border-white"
                                            :class="{'opacity-50': mode !== 'discount'}"
                                            @click="switchMode('discount')"
                                        >割引</div>
                                        <div class="modal-dis-btn text-center  border border-white"
                                            :class="{'opacity-50': mode !== 'reduction' }"
                                            @click="switchMode('reduction')"
                                        >値引</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-7 p-3">
                                <div class="modal-ac-keypad position-relative mx-auto text-primary">
                                    <div class="modal-ac-keypad-upper">
                                        <div class="modal-ac-keypad-upper-key border border-secondary" @click="typeNumber('7')">7</div>
                                        <div class="modal-ac-keypad-upper-key border border-secondary" @click="typeNumber('8')">8</div>
                                        <div class="modal-ac-keypad-upper-key border border-secondary" @click="typeNumber('9')">9</div>
                                        <div class="modal-ac-keypad-upper-backspace text-white border border-bottom-0 border-white" @click="typeBackspace()"><i class="fa-solid fa-delete-left"></i></div>
                                        <div class="modal-ac-keypad-upper-key border border-secondary" @click="typeNumber('4')">4</div>
                                        <div class="modal-ac-keypad-upper-key border border-secondary" @click="typeNumber('5')">5</div>
                                        <div class="modal-ac-keypad-upper-key border border-secondary" @click="typeNumber('6')">6</div>
                                        <div class="modal-ac-keypad-upper-minus text-white border border-bottom-0 border-white"><i class="fa-solid fa-minus"></i></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <div class="modal-ac-keypad-lower">
                                            <div class="modal-ac-keypad-lower-key border border-secondary" @click="typeNumber('1')">1</div>
                                            <div class="modal-ac-keypad-lower-key border border-secondary" @click="typeNumber('2')">2</div>
                                            <div class="modal-ac-keypad-lower-key border border-secondary" @click="typeNumber('3')">3</div>
                                            <div class="modal-ac-keypad-lower-key border border-secondary" @click="typeZero(10)">0</div>
                                            <div class="modal-ac-keypad-lower-key border border-secondary" @click="typeZero(100)">00</div>
                                            <div class="modal-ac-keypad-lower-key border border-white bg-secondary"></div>
                                        </div>
                                        <div class="modal-ac-keypad-lower-just text-white border border-white bg-secondary"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TODO:請求書払いフラグ -->

                        <div class="col-12 modal-ac-send bg-primary text-center text-white border border-white"
                            @click="$emit('updateDiscount', reduction, discount)"
                        >
                            割引・値引を適用する
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
        preDiscount : {
            type: Number,
            required: true,
        },
        preReduction : {
            type: Number,
            required: true,
        },
        amount: {
            type: Number,
            required: true,
        }
    },
    data() {
        return {
            mode: "discount", // discount: 割引, reduction: 値引
            discount: this.preDiscount,
            reduction: this.preReduction,
        }
    },
    methods: {
        switchMode: function(change) {
            this.mode = change;
            this.discount = 0;
            this.reduction = 0;
        },
        typeNumber: function(num) {
            if (this.mode === 'discount') {
                if (this.discount === 0) {
                    this.discount = Number(num);
                } else {
                    let typeNum = Number(this.discount + num);
                    if (typeNum <= 100) {
                        this.discount = typeNum;
                    } else {
                        this.discount = 100;
                    }
                }
                this.calcReduction();
            } else if (this.mode === 'reduction') {
                if (this.reduction === 0) {
                    this.reduction = Number(num);
                } else {
                    let typeNum = Number(this.reduction + num);
                    if (typeNum <= this.amount) {
                        this.reduction = typeNum;
                    } else {
                        this.reduction = this.amount;
                    }
                }
            }
        },
        typeZero: function(multi) {
            if (this.mode === 'discount') {
                let typeNum = this.discount * multi;
                if (typeNum < 100) {
                    this.discount = typeNum;
                } else {
                    this.discount = 100;
                }
                this.calcReduction();
            } else if (this.mode === 'reduction') {
                let typeNum = this.reduction * multi;
                if (typeNum < this.amount) {
                    this.reduction = typeNum;
                } else {
                    this.reduction = this.amount;
                }
            }
        },
        typeBackspace: function() {
            if (this.mode === 'discount') {
                this.discount = Number(String(this.discount).slice(0, -1));
                this.calcReduction();
            } else if (this.mode === 'reduction') {
                this.reduction = Number(String(this.reduction).slice(0, -1));
            }
        },
        calcReduction: function() {
            this.reduction = Math.floor(this.amount * (this.discount / 100));
        }
    }
}
</script>