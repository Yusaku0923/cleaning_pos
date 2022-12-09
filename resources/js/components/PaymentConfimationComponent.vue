<template>
    <div class="col-12 px-2 d-flex">
        <div class="col-6 px-4">
            <div class="col-12">
                <customer-info-component
                    :customer="customer"
                ></customer-info-component>
            </div>
            <div class="col-12 mt-2">
                <div class="card card-border col-12 py-2 h4 text-center">
                    入金未確認一覧
                </div>
                <div class="card card-border position-relative paymentconfirm-orders">
                    <div class="col-12 mb-2 bg-white position-sticky paymentconfirm-orders-column pt-3 border border-white">
                        <div class="card col-11 mx-auto bg-primary text-white">
                            <div class="d-flex">
                                <div class="col-6 text-center">期間</div>
                                <div class="col-2 text-center">伝票枚数</div>
                                <div class="col-2 text-center">お支払金額</div>
                                <div class="col-2 text-center">繰越</div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-11 mx-auto my-2 py-2 paymentconfirm-orders-order"
                        v-for="(invoice, index) in invoices"
                        :key="index"
                        :class="{ 'paymentconfirm-selected': index === selectedInvoice, 'paymentconfirm-unselected': index !== selectedInvoice }"
                        @click="selectInvoice(index)">
                        <div class="d-flex">
                            <div class="col-6 text-center">{{ dateFormater(invoice.period_start, 'YYYY/MM/DD') }} ~ {{ dateFormater(invoice.period_end, 'YYYY/MM/DD') }}</div>
                            <div class="col-2 text-center">{{ invoice.orders.length }}枚</div>
                            <div class="col-2 text-center">{{ invoice.amount.toLocaleString() }}円</div>
                            <div class="col-2 text-center">
                                <i class="fa-solid fa-check text-success" v-if="Boolean(invoice.has_carried_over)"></i>
                                <i class="fa-solid fa-xmark text-danger" v-else></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 px-4">
            <div class="col-12">
                <div class="card card-border col-12 py-2 h4 text-center">
                    伝票詳細
                </div>
            </div>
            <div class="card card-border col-12 paymentconfirm-operation">
                <div class="card-header text-center fw-bold">
                    操作
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-around">
                        <button class="paymentconfirm-operation-btn paymentconfirm-operation-btn-active text-white mt-2 bg-secondary" onclick="location.href='/'">
                            <div class="paymentconfirm-operation-btn-icon">
                                <i class="fa-solid fa-house"></i>
                            </div>
                            <div class="paymentconfirm-operation-btn-label text-center">
                                戻る
                            </div>
                        </button>
                        <button class="paymentconfirm-operation-btn text-black mt-2 py-green"
                            :disabled="selectedInvoice === ''"
                            :class="{ 'paymentconfirm-operation-btn-inactive': selectedInvoice === '' }"
                            @click="changeStatus()">
                            <div class="paymentconfirm-operation-btn-icon text-white">
                                <i class="fa-regular fa-circle-check"></i>
                            </div>
                            <div class="paymentconfirm-operation-btn-label text-center">
                                入金確認
                            </div>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card card-border position-relative mt-2 paymentconfirm-detail">
                <div class="col-12 mb-2 bg-white position-sticky paymentconfirm-detail-column pt-3 border border-white">
                    <div class="card col-11 mx-auto bg-primary text-white">
                        <div class="d-flex">
                            <div class="col-2 text-center">伝票No.</div>
                            <div class="col-3 text-center">預り日</div>
                            <div class="col-4 text-center">顧客名</div>
                            <div class="col-3 text-center">お支払金額</div>
                        </div>
                    </div>
                </div>
                <template v-if="orders !== []">
                    <div class="card col-11 mx-auto my-2 py-2 paymentconfirm-detail-clothes paymentconfirm-unselected"
                        v-for="order in orders"
                        :key="order.id">
                        <div class="d-flex">
                            <div class="col-2 text-center">{{ order.id }}</div>
                            <div class="col-3 text-center">{{ dateFormater(order.created_at, 'YYYY/MM/DD') }}</div>
                            <div class="col-4 text-center">{{ customer.name }}</div>
                            <div class="col-3 text-center">{{ order.amount.toLocaleString() }}円</div>
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
import moment from "moment";
export default ({
    props: {
        customer: {
            required: true
        },
        invoices: {
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
            selectedInvoice: '',
            invoiceId: '',
            orders: [],
            amount: 0,
            displayModal: false,
        }
    },
    methods: {
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
        },
        selectInvoice: function(id) {
            this.selectedInvoice = id;
            this.invoiceId = this.invoices[id].id;
            // this.amount = this.invoices[id].amount;
            this.items = [];
            for (const order of this.invoices[id].orders) {
                this.orders.push(order);
            }
        },
        changeStatus: function() {
            if (this.selectedInvoice !== '') {
                this.displayModal = !this.displayModal;
            }
        },
        account: function(payment) {
            this.changeStatus();
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('api/payment', {
                order_id: this.invoiceId,
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
