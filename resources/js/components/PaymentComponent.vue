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
                    未払い一覧
                </div>
                <div class="card position-relative payment-orders">
                    <div class="col-12 mb-2 bg-white position-sticky payment-orders-column pt-3 border border-white">
                        <div class="card col-11 mx-auto bg-primary text-white">
                            <div class="d-flex">
                                <div class="col-2 text-center">伝票No.</div>
                                <div class="col-3 text-center">預り日</div>
                                <div class="col-4 text-center">顧客名</div>
                                <div class="col-3 text-center">お支払金額</div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-11 mx-auto my-2 py-2 payment-orders-order"
                        v-for="(order, index) in orders"
                        :key="index"
                        :class="{ 'payment-selected': index === selectedOrder, 'payment-unselected': index !== selectedOrder }"
                        @click="selectOrder(index)">
                        <div class="d-flex">
                            <div class="col-2 text-center">{{ order.id }}</div>
                            <div class="col-3 text-center">{{ order.created_at }}</div>
                            <div class="col-4 text-center">{{ customer.name }}</div>
                            <div class="col-3 text-center">{{ order.amount.toLocaleString() }}円</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 px-4">
            <div class="col-12">
                <div class="card col-12 py-2 h4 text-center">
                    伝票詳細
                </div>
            </div>
            <div class="card col-12 payment-operation">
                <div class="card-header text-center fw-bold">
                    操作
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-around">
                        <button class="payment-operation-btn payment-operation-btn-active text-white mt-2 bg-secondary" onclick="location.href='/'">
                            <div class="payment-operation-btn-icon">
                                <i class="fa-solid fa-house"></i>
                            </div>
                            <div class="payment-operation-btn-label text-center">
                                戻る
                            </div>
                        </button>
                        <button class="payment-operation-btn text-white mt-2 uh-orange"
                            :disabled="selectedOrder === ''"
                            :class="{ 'payment-operation-btn-inactive': selectedOrder === '' }"
                            @click="changeStatus()">
                            <div class="payment-operation-btn-icon">
                                <i class="fa-solid fa-money-bill"></i>
                            </div>
                            <div class="payment-operation-btn-label text-center">
                                お支払
                            </div>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card position-relative mt-2 payment-detail">
                <div class="col-12 mb-2 bg-white position-sticky payment-detail-column pt-3 border border-white">
                    <div class="card col-11 mx-auto bg-primary text-white">
                        <div class="d-flex">
                            <div class="col-3 text-center">タグNo.</div>
                            <div class="col-7 text-center">商品名</div>
                            <div class="col-2 text-center">値段</div>
                        </div>
                    </div>
                </div>
                <template v-if="items !== []">
                    <div class="card col-11 mx-auto my-2 py-2 payment-detail-clothes payment-unselected"
                        v-for="item in items"
                        :key="item.id">
                        <div class="d-flex">
                            <div class="col-3 text-center">{{ item.tag }}</div>
                            <div class="col-7 text-center">{{ item.name }}</div>
                            <div class="col-2 text-center">{{ item.price }}円</div>
                        </div>
                    </div>
                </template>
                    
                <template v-else>
                    never selected order
                </template>
            </div>
        </div>

        <accounting-modal
            @close = "changeStatus"
            @account = "account"
            :amount="amount"
            v-if="displayModal"
        ></accounting-modal>
    </div>

</template>

<script>
import CustomerInfo from './CustomerInfoComponent';
import AccountingModal from './Modals/AccountingModalComponent';
export default ({
    props: {
        customer: {
            required: true
        },
        orders: {
            required: true
        },
        token: {
            type: String,
            required: true
        },
    },
    components: {
        'customer-info-component': CustomerInfo,
        'accounting-modal': AccountingModal,
    },
    data() {
        return {
            selectedOrder: '',
            orderId: '',
            items: [],
            amount: 0,
            displayModal: false,
        }
    },
    methods: {
        selectOrder: function(id) {
            this.selectedOrder = id;
            this.orderId = this.orders[id].id;
            this.amount = this.orders[id].amount;
            this.items = [];
            for (const item of this.orders[id].items) {
                this.items.push(item);
            }
        },
        changeStatus: function() {
            if (this.selectedOrder !== '') {
                this.displayModal = !this.displayModal;
            }
        },
        account: function(payment) {
            this.changeStatus();
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('api/payment', {
                order_id: this.orderId,
                payment: payment
            })
            .then(function (response) {
                location.reload();
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        }
    }
})
</script>
