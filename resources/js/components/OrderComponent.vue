<template>
 <!-- いずれcomponentにまとめる -->
    <div class="col-12 py-3 mx-auto d-flex justify-content-between position-relative">
        <div class="category-bar bg-light col-3 border-top border-bottom border-secondary">
            <div class="card py-5 px-2 border border-secondary" 
                @click="changeCategory(-1)"
                :class="{'odcreate-category-active': isActive === -1}"
            >
                よく注文される項目
            </div>

            <div class="card py-5 px-2 border border-secondary"
                v-for="(category, index) in categories"
                :key="category.id"
                @click="changeCategory(index)"
                :class="{'odcreate-category-active': isActive === index}"
            >
                {{ category.name }}
            </div>
        </div>

        <div class="clothes-bar bg-light col-5 border border-secondary pb-5">
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
                    <div class="position-absolute odcreate-clothes-tag"
                        :class="{
                            'odcreate-clothes-tag-one': clothes.tag_count === 1,
                            'odcreate-clothes-tag-two': clothes.tag_count === 2,
                            'odcreate-clothes-tag-more': clothes.tag_count >= 3,
                        }">
                        <i class="fa-solid fa-tag"></i>
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
                        <div class="position-absolute odcreate-clothes-tag"
                            :class="{
                                'odcreate-clothes-tag-one': clothes.tag_count === 1,
                                'odcreate-clothes-tag-two': clothes.tag_count === 2,
                                'odcreate-clothes-tag-more': clothes.tag_count >= 3,
                            }">
                            <i class="fa-solid fa-tag"></i>
                        </div>
                    </div>
                </template>
            </template>

            <div class="col-5 bg-white position-absolute odcreate-clothes-tagintro px-3">
                <div class="col-12 d-flex fs-18">
                    <div class="col-3 fw-bold">
                        タグ枚数
                    </div>
                    <div class="col-3 odcreate-clothes-tag odcreate-clothes-tag-one">
                        <i class="fa-solid fa-tag"></i><span class="ps-1">:１枚</span>
                    </div>
                    <div class="col-3 odcreate-clothes-tag odcreate-clothes-tag-two">
                        <i class="fa-solid fa-tag"></i><span class="ps-1">:２枚</span>
                    </div>
                    <div class="col-3 odcreate-clothes-tag odcreate-clothes-tag-more">
                        <i class="fa-solid fa-tag"></i><span class="ps-1">:３枚以上</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="slip-bar bg-light col-4 position-relative" v-if="step === 1">
            <div class="col-12 py-3 px-2 border border-secondary bg-white slip-header">
                計：{{ total }}点
            </div>

            <div class="order-list">
                <div class="col-12 py-3 px-2 border border-secondary bg-white position-relative"
                    v-for="i in reverseIndexes()"
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
                                <!-- {{ order[i].count * order[i].tag_count }} 点 -->
                                {{ order[i].count }} 点
                            </div>
                        </div>
                        <div class="col-5 order-text text-end text-primary">
                            {{ (order[i].count * order[i].price).toLocaleString() }} 円
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="col-6 text-start pt-2">
                            <span class="fs-22 fw-bold" v-if="(typeof tags[i] != 'undefined')">{{ tags[i] }}</span>
                            <span class="fs-22 fw-bold" v-else>なし</span>
                            
                        </div>
                        <div class="col-6 text-end pt-2">
                            <label class="fs-18">
                                <input
                                    type="checkbox"
                                    class="me-2"
                                    style="transform: scale(1.5);"
                                    :id="order[i].id"
                                    :value="order[i].id"
                                    v-model="dontIssueTagList"
                                    @change="issueTag()"
                                >
                                タグを発行しない
                            </label>
                        </div>
                    </div>
                    <div class="position-absolute odcreate-selected-tag fs-24"
                        :class="{
                            'odcreate-clothes-tag-one': order[i].tag_count === 1,
                            'odcreate-clothes-tag-two': order[i].tag_count === 2,
                            'odcreate-clothes-tag-more': order[i].tag_count >= 3,
                        }">
                        <i class="fa-solid fa-tag"></i>
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

        <div class="slip-bar bg-light col-4 border border-secondary position-relative" v-if="step === 2 || step === 3 || step === 4 || step === 5">
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
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row"
                    v-if="(step === 4 || step === 5)">
                    <div class="col-6 px-3">
                        お支払い方法
                    </div>
                    <div class="col-6 px-3 text-end text-primary" v-if="isInvoice">請求書払い</div>
                    <div class="col-6 px-3 text-end text-primary" v-if="notPaid">後払い</div>
                    <div class="col-6 px-3 text-end text-primary" v-else>現金預かり</div>
                </div>
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
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row"
                    v-if="step === 5 && !isInvoice && !notPaid">
                    <div class="col-6 px-3">
                        お支払い
                    </div>
                    <div class="col-6 px-3 text-end text-primary">
                        {{ payment.toLocaleString() }} 円
                    </div>
                </div>
                <div class="col-12 py-2 d-flex justify-content-between border-bottom border-1 border-secondary bill-row"
                    v-if="step === 5 && !isInvoice && !notPaid">
                    <div class="col-6 px-3">
                        お釣り
                    </div>
                    <div class="col-6 px-3 text-end text-primary">
                        {{ change.toLocaleString() }} 円
                    </div>
                </div>

                <template v-if="step === 2 || step === 3">
                    <div class="col-12 mt-3">
                        <div class="col-10 mx-auto d-flex pay-checkbox mb-3">
                            <label class="col-4 form-label" for="created-at">注文日</label>
                            <div class="col-8">
                                <input type="date" class="form-control" id="created-at" v-model="created_at">
                            </div>
                        </div>
                        <div class="col-10 mx-auto pay-checkbox form-check"
                            v-if="is_invoice">
                            <input type="checkbox" class="form-check-input" id="is-invoice" v-model="isInvoice" @click="notPaid = true">
                            <label class="form-check-label" for="is-invoice">請求書払い</label>
                        </div>
                        <div class="col-10 mx-auto pay-checkbox form-check"
                            v-if="!isInvoice">
                            <input type="checkbox" class="form-check-input" id="not-paid" v-model="notPaid">
                            <label class="form-check-label" for="not-paid">未収で登録する</label>
                        </div>
                        <div class="col-10 mx-auto pay-checkbox form-check">
                            <input type="checkbox" class="form-check-input" id="check-return" v-model="checkReturn">
                            <label class="form-check-label" for="check-return">返却確認する</label>
                        </div>
                    </div>

                </template>
                <div class="receipt mt-4">
                    <div class="col-11 mx-auto card bg-primary text-white p-3 mt-2"
                        v-show="step === 4 || step === 5"
                        @click="receiptReissue()">
                        {{ isInvoice ? 'レシート発行': 'レシート再発行' }}
                    </div>
                </div>
            </div>

            <div class="col-12 py-4 px-2 bg-primary text-white position-absolute bottom-0 text-center order-amount"
                @click="changeStep(3)"
                v-if="(step === 2 || step === 3) && !isInvoice && !notPaid">
                預り金入力
            </div>
            <div class="col-12 py-4 px-2 text-white position-absolute bottom-0 text-center order-amount"
                :style="{'background-color': '#2dbe5b'}"
                @click="issueSlip();"
                v-if="(step === 2 || step === 3) && (isInvoice || notPaid)">
                伝票発行
            </div>
            <a class="col-12 py-4 px-2 bg-primary text-white position-absolute bottom-0 text-center order-amount text-decoration-none"
                @click="CD_finish()"
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
        is_invoice: {
            type: Boolean,
            required: true
        },
        check_return: {
            type: Boolean,
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
        latest_tag: {
            Type: String,
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
            notPaid: this.is_invoice,
            isInvoice: this.is_invoice,
            checkReturn: this.check_return,
            showDiscount: false,
            indexes: [],
            order: {},
            orderForSend: {},
            dontIssueTagList: [],
            tags: {},
            total: 0,
            amount: 0, // with tax
            reduction: 0,
            discount: 0,
            payment: 0,
            change: 0,
            created_at: '',

            orderId: null
        }
    },
    components: {
        'discount-modal': DiscountModal,
        'accounting-modal': AccountingModal,
        'change-modal': ChangeModal,
        'receipt-printer': ReceiptPrinter,
    },
    mounted() {
        // ローカルIPは.envに
        let ePosDev = new epson.ePOSDevice();
        ePosDev.connect('192.168.0.214', 8008, function(data) {
            if(data == 'OK' || data == 'SSL_CONNECT_OK') {
                console.log('printer connected');
            } else {
                console.log(data);
            }
        }, {"eposprint" : true});
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

                this.total += clothes.tag_count;
                this.amount += this.order[clothes.id].price;

                this.CD_updateClothes(clothes.id);
                this.issueTag();
            }
        },
        increace: function (clothes) {
            let addedCount = this.order[clothes.id].count + 1;
            clothes.count = addedCount;
            this.$delete(this.order, clothes.id);
            this.$set(this.order, clothes.id, clothes);

            this.orderForSend[clothes.id] = addedCount;

            this.total += clothes.tag_count;
            this.amount += this.order[clothes.id].price;

            this.CD_updateClothes(clothes.id);
            this.issueTag();
        },
        decreace: function (clothes) {
            this.total -= clothes.tag_count;
            this.amount -= this.order[clothes.id].price;

            let substractedCount = this.order[clothes.id].count - 1;
            clothes.count = substractedCount;

            this.orderForSend[clothes.id] = substractedCount;

            this.$delete(this.order, clothes.id);
            if (clothes.count !== 0) {
                this.$set(this.order, clothes.id, clothes);
                this.CD_updateClothes(clothes.id);
            } else {
                this.CD_deleteClothes(clothes.id);
                this.indexes.splice(this.indexes.indexOf(clothes.id), 1);
                delete this.orderForSend[clothes.id];
            }
            this.issueTag();
        },
        reverseIndexes: function() {
            return this.indexes.slice().reverse();
        },
        issueTag: function() {
            // タグ初期化
            Object.keys(this.tags).forEach(key => {
                this.$delete(this.tags, key)
            });
            let tag_num = this.latest_tag;
            let tag = '';

            for (const i of this.indexes) {
                if (this.dontIssueTagList.includes(i)) {
                    continue;
                }
                if (this.orderForSend[i] === 1 && this.order[i].tag_count === 1) {
                    tag = String(tag_num).slice(0, -3) + '-' + String(tag_num).slice(-3);
                    tag_num = this.addTag(tag_num, 1);
                } else {
                    tag = String(tag_num).slice(0, -3) + '-' + String(tag_num).slice(-3);
                    tag_num = this.addTag(tag_num, this.orderForSend[i] * this.order[i].tag_count - 1);
                    tag += ' ~ ' + String(tag_num).slice(0, -3) + '-' + String(tag_num).slice(-3);
                    tag_num = this.addTag(tag_num, 1);
                }
                this.$set(this.tags, i, tag);
            }
        },
        addTag: function(num, count) {
            for (let i = 0; i < count; i++) {
                if (num >= 10999) {
                    num = 1000;
                } else {
                    num++;
                }
            }
            return num;
        },

        updateDiscount: function(reduction, discount) {
            this.reduction = reduction;
            this.discount = discount;

            this.CD_discount();
            this.switchDiscount();
        },

        issueSlip: async function() {
            this.order_id = await this.storeOrder();
            if (this.notPaid && !this.isInvoice) {
                this.$refs.child.printReceipt(this.order_id);
            }
            this.step = 5;
        },

        account: async function(payment) {
            if (payment < Math.trunc((this.amount - this.reduction))) {
                return;
            }
            this.step = 4;
            this.payment = payment;
            this.change = payment - Math.trunc((this.amount - this.reduction));

            this.CD_account();

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
            return await axios.post('/api/order/store', {
                manager_id: this.manager_id,
                customer_id: this.customer_id,
                indexes: this.indexes,
                order: this.orderForSend,
                amount: this.amount - this.reduction,
                reduction: this.reduction,
                discount: this.discount,
                payment: this.payment,
                not_paid: this.notPaid,
                invoice: this.isInvoice,
                check_return: this.checkReturn,
                dont_issue_tag_list: this.dontIssueTagList,
                created_at: this.created_at,
            })
            .then(function (response) {
                return response.data.order_id;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        // カスタマーディスプレイ表示情報(API => WebSocket)
        CD_updateClothes: function(id) {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('/api/broadcast', {
                event: 'update',
                total: this.total,
                id: id,
                name: this.order[id].name,
                count: this.order[id].count,
                price: this.order[id].count * this.order[id].price,
                amount: this.amount - this.reduction
            })
            .then(function (response) {
                return;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        CD_deleteClothes: function(id) {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('/api/broadcast', {
                event: 'delete',
                total: this.total,
                id: id,
                amount: this.amount - this.reduction
            })
            .then(function (response) {
                return;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        CD_discount: function() {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('/api/broadcast', {
                event: 'discount',
                reduction: this.reduction,
                discount: this.discount,
                amount: this.amount - this.reduction
            })
            .then(function (response) {
                return;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        CD_account: function() {
            console.log('account');
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('/api/broadcast', {
                event: 'account',
                payment: this.payment,
                change: this.change
            })
            .then(function (response) {
                return;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        CD_finish: function() {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('/api/broadcast', {
                event: 'finish'
            })
            .then(function (response) {
                return;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        
    },
    computed: {
    }
});
</script>