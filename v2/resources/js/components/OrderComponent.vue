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
                @click="changeCategory(-2)"
                :class="{'odcreate-category-active': isActive === -2}"
            >
                検索
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

        <div class="bg-light col-5 border border-secondary pb-5">
            <div class="col-12 p-2 mb-3" v-if="isActive === -2">
                <input type="text" class="form-control-lg col-12" v-model="searchText" @change="searchClothes">
            </div>
            <div class="clothes-bar">
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
    
                <template v-if="isActive === -2">         
                    <div class="col-12 card border border-secondary clothes-card position-relative p-2"
                        v-for="clothes in searchResult"
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
                        ({{ includedTax(amount - reduction, tax_rate, tax_rounding).toLocaleString() }} 円)
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
        ></receipt-printer>
    </div>
</template>

<script>
// 旧 resources/js/components/OrderComponent.vue (Vue 2) を Vue 3 に移したもの。画面 (template) は旧のまま。
// 変えた所: this.$set / this.$delete → 通常の代入・delete、API トークンをやめて Sanctum の Cookie、
// 注文は POST /api/orders (合計はサーバーで計算)、内消費税は includedTax (請求書と同じ端数処理)、
// プリンタ IP は設定 (printer_ip) から、顧客表示は customerDisplay.js (Task 7)。
import DiscountModal from './Modals/DiscountModalComponent.vue';
import AccountingModal from './Modals/AccountingModalComponent.vue';
import ChangeModal from './Modals/ChangeModalComponent.vue';
import ReceiptPrinter from './Functions/ReceiptPrinter.vue';
import { warmUpPrinter } from '../printer/EposPrinter.js';
import { includedTax, discountReduction } from '../utils/money.js';
import { pushCustomerDisplay } from '../customerDisplay.js';

export default {
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
            type: String,
            required: true
        },
        // 税率 (%)。旧は 1 + 税率/100 を :tax で受けていた
        tax_rate: {
            type: Number,
            required: true
        },
        // 'round' | 'floor' (config('pos.tax_rounding.invoice'))
        tax_rounding: {
            type: String,
            required: true
        },
        printer_ip: {
            type: String,
            default: null
        },
    },
    data() {
        return {
            step: 1,
            // ホームに戻るとき、選択中の担当者・顧客 (?m=&c=) を引き継ぐ (旧はセッション)
            route: '/' + window.location.search,
            isActive: -1,
            notPaid: this.is_invoice,
            isInvoice: this.is_invoice,
            checkReturn: this.check_return,
            showDiscount: false,
            indexes: [],
            searchText: '',
            searchResult: [],
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
        // 旧と同じく画面を開いたときにプリンタへ接続しておく (結果はログだけ)。旧は IP を直書きしていた
        if (this.printer_ip) {
            warmUpPrinter(this.printer_ip, 8008);
        }
        // 旧は GET /order/create で {event: 'order', name} を顧客表示へ送っていた (請求書払いの顧客以外)。v2 は開いたときに POST で送る
        if (!this.is_invoice) {
            pushCustomerDisplay({ event: 'order', name: this.customer_name });
        }
    },
    watch: {
        // 割引 (%) を適用したあとで品物を増減したら、値引額を今の合計から計算し直す
        // (旧は値引額が古いまま残り、画面の合計と割合が合わなくなっていた。サーバーは割合から計算する)
        amount: function (amount) {
            if (this.discount > 0) {
                this.reduction = discountReduction(amount, this.discount);
            }
        },
    },
    methods: {
        includedTax: includedTax,
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
        searchClothes: async function() {
            let self = this;
            return await axios.post('/api/clothes/search', {
                name: this.searchText
            })
            .then(function (response) {
                console.log(response.data.clothes);
                self.searchResult = response.data.clothes;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        add: function(clothes) {
            if (this.step === 1) {
                if (this.order[clothes.id]) {
                    let addedCount = this.order[clothes.id].count + 1;
                    clothes.count = addedCount;
                    delete this.order[clothes.id];

                    this.orderForSend[clothes.id] = addedCount;
                } else {
                    clothes.count = 1;
                    this.indexes.push(clothes.id);

                    this.orderForSend[clothes.id] = 1;
                }
                this.order[clothes.id] = clothes;

                this.total += clothes.tag_count;
                this.amount += this.order[clothes.id].price;

                // this.CD_updateClothes(clothes.id);
                this.issueTag();
            }
        },
        increace: function (clothes) {
            let addedCount = this.order[clothes.id].count + 1;
            clothes.count = addedCount;
            delete this.order[clothes.id];
            this.order[clothes.id] = clothes;

            this.orderForSend[clothes.id] = addedCount;

            this.total += clothes.tag_count;
            this.amount += this.order[clothes.id].price;

            // this.CD_updateClothes(clothes.id);
            this.issueTag();
        },
        decreace: function (clothes) {
            this.total -= clothes.tag_count;
            this.amount -= this.order[clothes.id].price;

            let substractedCount = this.order[clothes.id].count - 1;
            clothes.count = substractedCount;

            this.orderForSend[clothes.id] = substractedCount;

            delete this.order[clothes.id];
            if (clothes.count !== 0) {
                this.order[clothes.id] = clothes;
                // this.CD_updateClothes(clothes.id);
            } else {
                // this.CD_deleteClothes(clothes.id);
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
                delete this.tags[key];
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
                this.tags[i] = tag;
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

            // this.CD_discount();
            this.switchDiscount();
        },

        issueSlip: async function() {
            this.order_id = await this.storeOrder();
            if (!this.order_id) {
                return;
            }
            // 「レシート発行」ボタン (receiptReissue) は orderId を使う。旧はここで入れておらず null で印刷していた
            this.orderId = this.order_id;
            // if (this.notPaid && !this.isInvoice) {
            // }
            this.$refs.child.printReceipt(this.order_id);
            this.step = 5;
        },

        account: async function(payment) {
            if (payment < Math.trunc((this.amount - this.reduction))) {
                return;
            }
            this.step = 4;
            this.payment = payment;
            this.change = payment - Math.trunc((this.amount - this.reduction));

            // this.CD_account();

            // order登録API
            let order_id = await this.storeOrder();
            if (!order_id) {
                return;
            }
            // お釣りはサーバーが保存した合計 (レシートに印刷される値) で出し直す
            this.change = payment - (this.amount - this.reduction);

            // レシート発行
            console.log('printReceipt1');
            this.$refs.child.printReceipt(order_id);

            // manager_id = 1の場合は二回印刷
            if (this.manager_id === 1) {
                // 少し間隔を空けて二回目を印刷

                setTimeout(() => {
                    console.log('printReceipt2');
                    this.$refs.child.printReceipt(order_id);
                }, 1000);
            }

            this.orderId = order_id;
        },

        receiptReissue: function() {
            this.$refs.child.printReceipt(this.orderId, true); // 再発行のためisReissue=true
        },

        storeOrder: async function() {
            const expected = Math.trunc(this.amount - this.reduction);
            return await axios.post('/api/orders', {
                manager_id: this.manager_id,
                customer_id: this.customer_id,
                // 追加した順 (旧 indexes) に、数量とタグを発行するか
                items: this.indexes.map((id) => ({
                    clothes_id: id,
                    qty: this.orderForSend[id],
                    issue_tag: !this.dontIssueTagList.includes(id),
                })),
                // 割引 (%) ならその割合、値引 (円) なら金額。合計はサーバーが商品マスタの単価から計算する
                discount_ratio: this.discount > 0 ? this.discount : null,
                discount_amount: this.discount > 0 ? null : this.reduction,
                deposit: this.payment,
                is_invoice: this.isInvoice,
                not_paid: this.notPaid,
                check_return: this.checkReturn,
                created_at: this.created_at || null,
            })
            .then((response) => {
                if (response.data.total !== expected) {
                    console.warn('画面の合計とサーバーの合計が違います', expected, response.data.total);
                }
                // 保存した合計 (サーバーの計算。レシートもこの値) を画面の正とする
                this.reduction = this.amount - response.data.total;
                return response.data.order_id;
            })
            .catch(function (error) {
                console.log(error);
                alert('注文を登録できませんでした。');
                return;
            });
        },
        // カスタマーディスプレイ表示情報 (customerDisplay.js が POST /api/customer-display に送る)
        CD_updateClothes: function(id) {
            pushCustomerDisplay({
                event: 'update',
                total: this.total,
                id: id,
                name: this.order[id].name,
                count: this.order[id].count,
                price: this.order[id].count * this.order[id].price,
                amount: this.amount - this.reduction
            });
        },
        CD_deleteClothes: function(id) {
            pushCustomerDisplay({
                event: 'delete',
                total: this.total,
                id: id,
                amount: this.amount - this.reduction
            });
        },
        CD_discount: function() {
            pushCustomerDisplay({
                event: 'discount',
                reduction: this.reduction,
                discount: this.discount,
                amount: this.amount - this.reduction
            });
        },
        CD_account: function() {
            console.log('account');
            pushCustomerDisplay({
                event: 'account',
                payment: this.payment,
                change: this.change
            });
        },
        CD_finish: function() {
            pushCustomerDisplay({
                event: 'finish'
            });
        },

    },
    computed: {
    }
};
</script>
