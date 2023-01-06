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

        <div class="cd-2 position-relative d-flex" v-if="phase === 2">
            <!--
            表示項目
            ・商品リスト（名前、個数、小計）
            ・金額まわり（合計、割引、お預かり、お釣り）
            -->
            <div class="col-7 cd-2-order">
                <div class="cd-2-bar"></div>
                <div class="cd-2-bar2"></div>
                <!-- <div class="col-11 mx-auto px-2 cd-2-order-title">
                    注文リスト
                </div> -->
                <div class="col-11 mx-auto px-2 overflow-scroll cd-2-order-list" id="order-area" @click="scroll()">
                    <div id="list-top"></div>
                    <div class="col-12 my-2 py-1 px-1 d-flex cd-2-order-list-row" v-for="i in 22" :key="i" :id="['row-' + i]">
                        <div class="col-7 cd-2-order-list-row-name">
                            Yシャツ
                        </div>
                        <div class="col-2 text-center cd-2-order-list-row-count">
                            × {{ i }}
                        </div>
                        <div class="col-3 text-end cd-2-order-list-row-price">
                            1,000円
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-5 cd-2-result">
                <img class="cd-2-result-hanger" src="/img/hanger.png" alt="">
                <!-- <img class="cd-2-result-tshorts" src="/img/tshorts.png" alt=""> -->
                <div class="cd-2-result-tshorts d-flex">
                    <div class="cd-2-result-tshorts-left">

                    </div>
                    <div class="cd-2-result-tshorts-center">
                        <div class="cd-2-result-tshorts-center-neck"></div>
                        <div class="cd-2-result-tshorts-center-chest"></div>
                    </div>
                    <div class="cd-2-result-tshorts-right">

                    </div>
                </div>
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
            }, 1200);
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