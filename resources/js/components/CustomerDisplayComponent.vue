<template>
    <div class="cd col-12">
        <div class="cd-1" v-if="phase === 1" @click="transferOrder()">
            <div
                class="mx-auto cd-1-welcome col-12"
                :class="{'cd-1-welcome-anim-1': phase1_1anim, 'cd-1-welcome-anim-2': phase1_2anim}"
            >いらっしゃいませ</div>
            <div
                class="mx-auto cd-1-name col-12 cd-1-smooth"
                :class="{'cd-1-name-anim-1': phase1_1anim, 'cd-1-name-anim-2': phase1_2anim}"
            >{{ state.customer }}様</div>
        </div>

        <div class="cd-2 position-relative" v-if="phase === 2" :class="{'cd-2-end': phase3_1anim}" @click="finishOrder()">
            <div class="cd-2-bar"></div>
            <div class="col-12 d-flex">
                <div class="col-6 cd-2-order">
                    <div>
                        <div class="cd-2-order-bar"></div>

                        <div class="col-11 mx-auto px-3 overflow-scroll cd-2-order-list" id="order-area" @click="scroll()">
                            <div id="list-top"></div>
                            <div class="col-12 my-2 py-1 px-1 d-flex cd-2-order-list-row" v-for="i in 22" :key="i" :id="['row-' + i]">
                                <div class="col-6 cd-2-order-list-row-name">
                                    Yシャツ
                                </div>
                                <div class="col-2 text-start cd-2-order-list-row-count">
                                    × {{ i }}
                                </div>
                                <div class="col-4 text-end cd-2-order-list-row-price">
                                    1,000円
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 cd-2-result">
                    <div class="cd-2-result-bar"></div>
                    <img class="cd-2-result-hanger" src="/img/hanger.svg" alt="">
                    <img class="cd-2-result-tshorts" src="/img/tshorts.svg" alt="">
                    <div class="cd-2-result-field fw-bold">
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-total">
                            <div class="col-5">
                                点数
                            </div>
                            <div class="col-7 text-end">
                                10点
                            </div>
                        </div>
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-amount">
                            <div class="col-4">
                                割引き
                            </div>
                            <div class="col-8 text-end">
                                <div class="col-12">200円</div>
                                <div class="col-12">(10%割引き)</div>
                            </div>
                        </div>
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-amount">
                            <div class="col-5">
                                合計金額
                            </div>
                            <div class="col-7 text-end">
                                2,000円
                            </div>
                        </div>
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-payment">
                            <div class="col-5">
                                お預かり
                            </div>
                            <div class="col-7 text-end">
                                2,000円
                            </div>
                        </div>
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-change">
                            <div class="col-5">
                                お釣り
                            </div>
                            <div class="col-7 text-end">
                                0円
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cd-3" v-if="phase === 3" :class="{'cd-3-end': phase3_2anim}">
            <div class="cd-3-string cd-3-string-l"></div>
            <div class="cd-3-string cd-3-string-r"></div>
            <div class="cd-3-board">
                <div class="col-12 text-center fw-bold cd-3-board-text">ありがとうございました。</div>
                <div class="col-12 text-center fw-bold cd-3-board-text">またのご利用お待ちしております。</div>
            </div>
        </div>
    </div>
</template>

<script>
export default ({
    data() {
        return {
            phase: 1,
            state: {
                customer: '',
                order: {},
                amount: 0,
            },
            scrollList: ['list-top', 'row-8', 'row-15'],
            phase1_1anim: false,
            phase1_2anim: false,
            phase3_1anim: false,
            phase3_2anim: false,
        }
    },
    mounted() {
        window.Echo.channel('cleaning-pos')
            .listen('.customer-display', res => {
                switch(res.event) {
                    case 'customer':
                        this.setCustomer(res);
                        break;
                    case 'order':
                        this.transferOrder(res);
                        break;
                    case 'add':
                        this.addOrder(res);
                        break;
                    case 'reduce':
                        this.reduceOrder(res);
                        break;
                    case 'confirm':
                        this.transferConfirm(res);
                        break;
                    case 'discount':
                        this.discount(res);
                        break;
                    case 'account':
                        this.account(res);
                        break;
                    case 'finish':
                        this.finishOrder(res);
                        break;
                    default:
                        console.log(res)
                        break;
                }
            });
    },
    methods: {
        setCustomer: function(res) {
            this.$set(this.state, 'customer', res.name);
            this.phase1_1anim = true;
        },
        transferOrder: function(res) {
            this.phase1_2anim = true;
            setTimeout(() => {
                this.phase = 2;
                this.scroll();
            }, 1000);
        },
        addOrder: function() {

        },
        reduceOrder: function() {

        },
        transferConfirm: function() {

        },
        discount: function() {

        },
        account: function() {

        },
        finishOrder: function() {
            this.phase3_1anim = true;
            setTimeout(() => {
                this.phase1_1anim = false;
                this.phase1_2anim = false;
                this.phase3_1anim = false;
                this.phase = 3;
            }, 1200);
            setTimeout(() => {
                this.phase3_2anim = true;
            }, 6200);
            setTimeout(() => {
                this.phase = 1;
                this.phase3_2anim = false;
            }, 8000);
        },
        scroll: function() {
            let index = 0;
            setInterval(function() {
                let row = document.getElementById(this.scrollList[index]);
                row.scrollIntoView({
                    behavior : 'smooth',
                });
                if (index >= this.scrollList.length - 1) {
                    index = 0;
                } else {
                    index++;
                }
            }.bind(this), 5000)
        }
    }
});
</script>