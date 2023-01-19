<template>
    <div class="cd col-12">
        <div class="cd-1" v-if="phase === 1">
            <div
                class="mx-auto cd-1-welcome col-12"
                :class="{'cd-1-welcome-anim-1': phase1_1anim, 'cd-1-welcome-anim-2': phase1_2anim}"
            >いらっしゃいませ</div>
            <div
                class="mx-auto cd-1-name col-12 cd-1-smooth"
                :class="{'cd-1-name-anim-1': phase1_1anim, 'cd-1-name-anim-2': phase1_2anim}"
            >{{ state.customer }}様</div>
        </div>

        <div class="cd-2 position-relative" v-if="phase === 2" :class="{'cd-2-end': phase3_1anim}">
            <div class="cd-2-bar"></div>
            <div class="col-12 d-flex">
                <div class="col-6 cd-2-order">
                    <div>
                        <div class="cd-2-order-bar"></div>

                        <div class="col-11 mx-auto px-3 overflow-scroll cd-2-order-list" id="order-area">
                            <div id="list-top"></div>
                            <div class="col-12 my-2 py-1 px-1 d-flex cd-2-order-list-row" v-for="order in state.order" :key="order.id" :id="['row-' + order.id]">
                                <div class="col-6 cd-2-order-list-row-name">
                                    {{ order.name }}
                                </div>
                                <div class="col-2 text-start cd-2-order-list-row-count">
                                    × {{ order.count }}
                                </div>
                                <div class="col-4 text-end cd-2-order-list-row-price">
                                    {{ order.price.toLocaleString() }}円
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 cd-2-result">
                    <div class="cd-2-result-bar"></div>
                    <img class="cd-2-result-hanger" src="/img/hanger.svg" alt="">
                    <img class="cd-2-result-tshorts" src="/img/tshorts.svg" alt="">
                    <div class="cd-2-result-field">
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-total">
                            <div class="col-6 cd-2-result-field-label">
                                点数
                            </div>
                            <div class="col-6 text-end cd-2-result-field-value">
                                {{ state.total }}点
                            </div>
                        </div>
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-amount" v-if="state.reduction !== 0">
                            <div class="col-4 cd-2-result-field-label">
                                割引き
                            </div>
                            <div class="col-8 text-end cd-2-result-field-value">
                                <div class="col-12">{{ state.reduction.toLocaleString() }}円</div>
                                <div class="col-12" v-if="state.discount !== 0">({{ state.discount }}%引き)</div>
                            </div>
                        </div>
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-amount">
                            <div class="col-6 cd-2-result-field-label">
                                合計金額
                            </div>
                            <div class="col-6 text-end cd-2-result-field-value">
                                {{ state.amount.toLocaleString() }}円
                            </div>
                        </div>
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-payment" v-if="state.payment !== 0">
                            <div class="col-6 cd-2-result-field-label">
                                お預かり
                            </div>
                            <div class="col-6 text-end cd-2-result-field-value">
                                {{ state.payment.toLocaleString() }}円
                            </div>
                        </div>
                        <div class="col-12 d-flex px-3 mb-2 cd-2-result-field-change" v-if="state.payment !== 0">
                            <div class="col-6 cd-2-result-field-label">
                                お釣り
                            </div>
                            <div class="col-6 text-end cd-2-result-field-value">
                                {{ state.change.toLocaleString() }}円
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
                total: 0,
                order: [],
                reduction: 0,
                discount: 0,
                amount: 0,
                payment: 0,
                change: 0,
            },
            row: 0,
            scrollList: ['list-top'],
            scrollEvent: -1,
            cancelOrderEvent: -1,
            finishOrderEvent: -1,
            phase1_1anim: false,
            phase1_2anim: false,
            phase3_1anim: false,
            phase3_2anim: false,
        }
    },
    mounted() {
        console.log(document.documentElement.clientWidth);
        console.log(document.documentElement.clientHeight);
        window.addEventListener('resize', this.handleResize);
        window.Echo.channel('cleaning-pos')
            .listen('.customer-display', req => {
                req = req.message; // コイツ
                switch(req.event) {
                    case 'customer':
                        this.setCustomer(req);
                        break;
                    case 'order':
                        this.transferOrder(req);
                        break;
                    case 'update':
                        this.update(req);
                        break;
                    case 'delete':
                        this.delete(req);
                        break;
                    case 'discount':
                        this.discount(req);
                        break;
                    case 'account':
                        this.account(req);
                        break;
                    case 'finish':
                        this.finishOrder(req);
                        break;
                    default:
                        console.log(req)
                        break;
                }
            });
    },
    methods: {
        handleResize: function() {
            console.log(document.documentElement.clientWidth);
            console.log(document.documentElement.clientHeight);
        },
        setCustomer: function(req) {
            if (this.phase !== 1) {
                this.initialize();
            }
            this.$set(this.state, 'customer', req.name);
            this.phase1_1anim = true;
        },
        transferOrder: function(req) {
            this.phase1_2anim = true;
            // 注文初期化
            this.$set(this.state, 'total', 0);
            this.$set(this.state, 'order', []);
            this.$set(this.state, 'reduction', 0);
            this.$set(this.state, 'discount', 0);
            this.$set(this.state, 'amount', 0);
            this.$set(this.state, 'payment', 0);
            this.$set(this.state, 'change', 0);
            setTimeout(() => {
                this.phase = 2;
            }, 1000);
            // 長時間(10分)放置の場合、強制終了
            this.cancelOrderEvent = setTimeout(() => {
                this.initialize();
            }, 600000);
        },
        update: function(req) {
            let index = this.state.order.findIndex((elem) => {
                return elem.id === req.id;
            });

            let order = {
                id: req.id,
                name: req.name,
                count: req.count,
                price: req.price,
            };

            if (index !== -1) {
                this.state.order.splice(index, 1, order);
            } else {
                this.state.order.push(order);

                this.row = this.state.order.length;
                // 6 * x + 2[x > 0]行ずつでスクロールリストチェックを行う
                if (this.row >= 8 && this.row % 6 === 2) {
                    let tg = 'row-' + this.row;
                    if (this.scrollList.indexOf(tg) === -1) this.scrollList.push(tg);
                    // スクロールイベントを実行中でなければ実行
                    if (this.scrollEvent !== -1) this.scroll();
                }
            }

            this.$set(this.state, 'total', req.total);
            this.$set(this.state, 'amount', req.amount);
        },
        delete: function(req) {
            let index = this.state.order.findIndex((elem) => {
                return elem.id === req.id;
            });

            if (index !== -1) {
                this.state.order.splice(index, 1);

                this.row = this.state.order.length;
                // 6 * x + 2[x > 0]行ずつでスクロールリストチェックを行う
                if (this.row >= 8 && this.row % 6 === 2) {
                    let tg = 'row-' + this.row;
                    let tgIndex = this.scrollList.indexOf(tg);
                    if (tgIndex === -1) this.scrollList.splice(tgIndex, 1);
                    // スクロールイベントの実行の必要がなければ中断
                    if (this.scrollList === 1) {
                        clearInterval(this.scrollEvent);
                        this.scrollEvent = -1;
                    }
                }
            }

            this.$set(this.state, 'total', req.total);
            this.$set(this.state, 'amount', req.amount);
        },
        discount: function(req) {
            this.$set(this.state, 'reduction', req.reduction);
            this.$set(this.state, 'discount', req.discount);
            this.$set(this.state, 'amount', req.amount);
        },
        account: function(req) {
            this.$set(this.state, 'payment', req.payment);
            this.$set(this.state, 'change', req.change);
            // 会計後１分放置していた場合、強制終了
            this.finishOrderEvent = setTimeout(() => {
                this.finishOrder();
            }, 60000);
        },
        finishOrder: function() {
            this.phase3_1anim = true;
            if (this.scrollEvent !== -1) {
                clearInterval(this.scrollEvent);
            }
            if (this.cancelOrderEvent !== -1) {
                clearTimeout(this.cancelOrderEvent);
            }
            if (this.finishOrderEvent !== -1) {
                clearTimeout(this.finishOrderEvent);
            }
            setTimeout(() => {
                this.phase = 3;
            }, 1200);
            setTimeout(() => {
                this.phase3_2anim = true;
            }, 6200);
            setTimeout(() => {
                this.initialize();
            }, 8000);
        },
        scroll: function() {
            let index = 0;
            this.scrollEvent = setInterval(() => {
                let row = document.getElementById(this.scrollList[index]);
                row.scrollIntoView({
                    behavior : 'smooth',
                });
                if (index >= this.scrollList.length - 1) {
                    index = 0;
                } else {
                    index++;
                }
            }, 5000);
        },
        initialize: function() {
            this.state = {
                customer: '',
                total: 0,
                order: [],
                reduction: 0,
                discount: 0,
                amount: 0,
                payment: 0,
                change: 0,
            };
            this.scrollList = ['list-top'];
            this.row = 0;
            this.scrollEvent = -1;
            this.cancelOrderEvent = -1,
            this.finishOrderEvent = -1,
            this.phase1_1anim = false;
            this.phase1_2anim = false;
            this.phase3_1anim = false;
            this.phase3_2anim = false;

            this.phase = 1;
        },
    }
});
</script>