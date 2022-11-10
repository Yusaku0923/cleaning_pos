<template>
 <!-- いずれcomponentにまとめる -->
    <div class="col-12 py-3 mx-auto d-flex justify-content-between">
        <div class="category-bar col-3 border-top border-bottom border-secondary">
            <div class="card py-5 px-2 border border-secondary" 
                @click="changeCategory(-1)"
                v-bind:class="{'active': isActive === -1}"
            >
                よく注文される項目
            </div>

            <div class="card py-5 px-2 border border-secondary"
                v-for="(category, index) in categories"
                :key="category.id"
                @click="changeCategory(index)"
                v-bind:class="{'active': isActive === index}"
            >
                {{ category.name }}
            </div>
        </div>

        <div class="clothes-bar col-5 border border-secondary">
            <template v-if="isActive === -1">
                <div class="card border border-secondary clothes-card position-relative p-2"
                    v-for="clothes in often_ordered"
                    :key="clothes.id"
                    @click="add(clothes)">
                    <div class="text-nowrap overflow-auto">
                        {{ clothes.name }}
                    </div>
                    <div class="position-absolute bottom-0 text-primary">
                        {{ clothes.price.toLocaleString() }} 円
                    </div>
                </div>
            </template>

            <template v-for="(category, index) in categories">
                <template v-if="isActive === index">
                    <div class="card border border-secondary clothes-card position-relative p-2"
                        v-for="clothes in category.clothes"
                        :key="clothes.id"
                        @click="add(clothes)">
                        <div class="text-nowrap overflow-auto">
                            {{ clothes.name }}
                        </div>
                        <div class="position-absolute bottom-0 text-primary">
                            {{ clothes.price.toLocaleString() }} 円
                        </div>
                    </div>
                </template>
            </template>
        </div>

        <div class="slip-bar col-4 position-relative" v-if="step === 1">
            <div class="col-12 py-3 px-2 border border-secondary bg-white slip-header">
                計：{{ total }}点
            </div>

            <div class="order-list">
                <div class="col-12 py-3 px-2 border border-secondary bg-white"
                    v-for="i in indexes"
                    :key="i">
                    <div class="col-12 px-2 order-title">{{ order[i].name }}</div>
                    <div class="col-12 mt-2 d-flex justify-content-between order-detail">
                        <div class="col-5 d-flex justify-content-between">
                            <button class="card border border-2 border-secondary text-secondary p-2" @click="decreace(order[i])">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <button class="card border border-2 border-secondary text-secondary p-2" @click="increace(order[i])">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <div class="text-primary order-text">
                                {{ order[i].count }} 点
                            </div>
                        </div>
                        <div class="col-5 order-text text-end text-primary">
                            {{ (order[i].count * order[i].price).toLocaleString() }} 円
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 py-4 px-2 text-white position-absolute bottom-0 d-flex justify-content-between"
                @click="changeStep(2)"
                :class="{ 'bg-primary': total !== 0, 'bg-secondary': total === 0 }">
                <div class="col-8 order-amount">
                    <span style="font-size:20px;">税込</span> {{ Math.trunc((amount - reduction)).toLocaleString() }} 円
                </div>
                <div class="col-4 text-end to-bill">
                    お会計へ　<i class="fa-solid fa-chevron-right pl-2"></i>
                </div>
            </div>

        </div>

        <div class="slip-bar col-4 border border-secondary position-relative" v-if="step === 2 || step === 3 || step === 4 || step === 5">
            <div class="col-12 py-3 px-2 border-bottom border-secondary bg-white d-flex justify-content-between slip-header">
                <div class="col-3 text-primary" @click="changeStep(1)" v-if="step !== 5">
                        <i class="fa-solid fa-chevron-left"></i> 戻る
                </div>
                <div class="col-3" v-if="step === 5"></div>
                <div class="col-9 text-start">
                    {{ customer_name }} 様
                </div>
            </div>

            <div class="bill-detail">
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row">
                    <div class="col-6 px-3">
                        数量
                    </div>
                    <div class="col-6 px-3 text-end text-primary">
                        {{ total }}点
                    </div>
                </div>
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row">
                    <div class="col-6 px-3">
                        小計
                    </div>
                    <div class="col-6 px-3 text-end text-primary">
                        {{ Math.trunc((amount)).toLocaleString() }} 円
                    </div>
                </div>
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row">
                    <div class="col-6 px-3">
                        値引・割引
                    </div>
                    <div class="col-6 px-3 text-end text-primary">
                        {{ reduction }} 円 <span v-if="discount > 0">({{ discount }}%)</span>
                    </div>
                </div>
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row"
                    v-if="step !== 5"
                    @click="switchDiscount()">
                    <div class="col-8 px-3">
                        値引・割引を追加
                    </div>
                    <div class="col-2 px-3 text-end">
                        <i class="fa-solid fa-chevron-right text-secondary"></i>
                    </div>
                </div>

                <div class="col-12 py-2 d-flex justify-content-between border-top border-bottom border-1 border-secondary bill-row">
                    <div class="col-4 px-3" style="line-height: 2.6">
                        合計
                    </div>
                    <div class="col-8 px-3 text-end bill-total text-primary">
                        {{ Math.trunc((amount - reduction)).toLocaleString() }} 円
                    </div>
                </div>
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row" v-if="step !== 5">
                    <div class="col-6 px-3 text-secondary">
                        内消費税10%
                    </div>
                    <div class="col-6 px-3 text-end text-secondary">
                        ({{ Math.trunc((amount - reduction) - ((amount - reduction) / tax)).toLocaleString() }} 円)
                    </div>
                </div>
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row" v-if="step === 5">
                    <div class="col-6 px-3">
                        お支払い
                    </div>
                    <div class="col-6 px-3 text-end text-primary">
                        {{ payment.toLocaleString() }} 円
                    </div>
                </div>
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row" v-if="step === 5">
                    <div class="col-6 px-3">
                        お釣り
                    </div>
                    <div class="col-6 px-3 text-end text-primary">
                        {{ change.toLocaleString() }} 円
                    </div>
                </div>

                <div class="receipt mt-4">
                    <div class="col-11 mx-auto card bg-primary text-white p-3 mt-2"
                        v-show="step === 4 || step === 5"
                        @click="receiptReissue()">
                        レシート再発行
                    </div>
                </div>
            </div>

            <div class="col-12 py-4 px-2 bg-primary text-white position-absolute bottom-0 text-center order-amount"
                @click="changeStep(3)"
                v-if="step === 2 || step === 3">
                預り金入力
            </div>
            <a class="col-12 py-4 px-2 bg-primary text-white position-absolute bottom-0 text-center order-amount text-decoration-none"
                :href="route"
                v-if="step === 4 || step === 5"
            >ホームに戻る</a>

        </div>

        <discount-modal
            @close = "switchDiscount"
            @updateDiscount = "updateDiscount"
            :amount="amount"
            :preReduction="reduction"
            :preDiscount="discount"
            v-if="showDiscount"
        ></discount-modal>

        <accounting-modal
            @close = "changeStep"
            @account = "account"
            :amount="amount - reduction"
            v-if="step === 3"
        ></accounting-modal>

        <change-modal
            @close = "changeStep"
            :change="change"
            v-if="step === 4"
        ></change-modal>

        <receipt-printer
            ref="child"
            :token="token"
        ></receipt-printer>
    </div>
</template>

<script>
import DiscountModal from './Modals/DiscountModalComponent';
import AccountingModal from './Modals/AccountingModalComponent';
import ChangeModal from './Modals/ChangeModalComponent';
import ReceiptPrinter from './Functions/ReceiptPrinter';

export default ({
    props: {
        manager_id: {
            type: Number,
            required: true
        },
        customer_id: {
            type: Number,
            required: true
        },
        customer_name: {
            type: String,
            required: true
        },
        categories: {
            type: Array,
            required: true
        },
        often_ordered: {
            type: Array,
            required: true
        },
        tax: {
            required: true
        },
        token: {
            type: String,
            required: true
        },
    },
    data() {
        return {
            step: 1,
            route: '/',
            isActive: -1,
            showDiscount: false,
            indexes: [],
            order: {},
            orderForSend: {},
            total: 0,
            amount: 0, // without tax
            reduction: 0,
            discount: 0,
            payment: 0,
            change: 0,

            orderId: null
        }
    },
    components: {
        'discount-modal': DiscountModal,
        'accounting-modal': AccountingModal,
        'change-modal': ChangeModal,
        'receipt-printer': ReceiptPrinter,
    },
    methods: {
        changeCategory: function (num) {
            this.isActive = num;
        },
        changeStep: function (num) {
            if (this.total !== 0) {
                this.step = num;
            }
        },
        switchDiscount: function () {
            this.showDiscount = !this.showDiscount;
        },
        add: function(clothes) {
            if (this.step === 1) {
                if (this.order[clothes.id]) {
                    let addedCount = this.order[clothes.id].count + 1;
                    clothes.count = addedCount;
                    this.$delete(this.order, clothes.id);

                    this.orderForSend[clothes.id] = addedCount;
                } else {
                    clothes.count = 1;
                    this.indexes.push(clothes.id);

                    this.orderForSend[clothes.id] = 1;
                }
                this.$set(this.order, clothes.id, clothes);

                this.total++;
                this.amount += this.order[clothes.id].price;
            }
        },
        increace: function (clothes) {
            let addedCount = this.order[clothes.id].count + 1;
            clothes.count = addedCount;
            this.$delete(this.order, clothes.id);
            this.$set(this.order, clothes.id, clothes);

            this.orderForSend[clothes.id] = addedCount;

            this.total++;
            this.amount += this.order[clothes.id].price;
        },
        decreace: function (clothes) {
            this.total--;
            this.amount -= this.order[clothes.id].price;

            let substractedCount = this.order[clothes.id].count - 1;
            clothes.count = substractedCount;

            this.orderForSend[clothes.id] = substractedCount;

            this.$delete(this.order, clothes.id);
            if (clothes.count !== 0) {
                this.$set(this.order, clothes.id, clothes);
            } else {
                this.indexes.splice(this.indexes.indexOf(clothes.id), 1);
                delete this.orderForSend[clothes.id];
            }
        },

        updateDiscount: function(reduction, discount) {
            this.reduction = reduction;
            this.discount = discount;

            this.switchDiscount();
        },

        account: async function(payment) {
            if (payment < Math.trunc((this.amount - this.reduction))) {
                return;
            }
            this.step = 4;
            this.payment = payment;
            this.change = payment - Math.trunc((this.amount - this.reduction));

            // order登録API
            let order_id = await this.storeOrder();
           
            // レシート発行
            this.$refs.child.printReceipt(order_id);
            this.orderId = order_id;
        },

        receiptReissue: function() {
            this.$refs.child.printReceipt(this.orderId);
        },

        storeOrder: async function() {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            return await axios.post('/api/order', {
                manager_id: this.manager_id,
                customer_id: this.customer_id,
                order: this.orderForSend,
                amount: this.amount - this.reduction, // with tax
                reduction: this.reduction,
                discount: this.discount,
                payment: this.payment,
                invoice: false,
            })
            .then(function (response) {
                return response.data.order_id;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        }
    }
});
</script>