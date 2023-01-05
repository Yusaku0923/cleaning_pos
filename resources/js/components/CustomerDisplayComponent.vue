<template>
    <div class="cd col-12">
        <div class="cd-1" v-if="phase === 1">
            <!-- <div class="cd-1-welcome col-12" >いらっしゃいませ</div> -->
            <div class="cd-1-welcome col-12 cd-1-smooth" :class="{'cd-1-animated': phase1anim}">いらっしゃいませ　{{ state.customer }}様</div>
        </div>

        <div class="cd-2" v-if="phase === 2">

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
                phase1anim: false
            }
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
            this.phase1anim = true;
            // window.setTimeout(() => {
        },
        transferOrder: function() {

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

        }
    }
});
</script>