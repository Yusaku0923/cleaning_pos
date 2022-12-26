<template>
    <div class="col-12">
        <div class="col-12 px-2 d-flex">
            <div class="col-6 px-4">
                <div class="col-12">
                    <customer-info-component
                        :customer="customer"
                    ></customer-info-component>
                    <div class="col-12 mt-2">
                        <div class="card card-border col-12 py-2 h4 text-center">
                            伝票一覧
                        </div>
                    </div>
                    <div class="card card-border position-relative odlist-orders">
                        <div class="col-12 mb-2 bg-white position-sticky odlist-orders-column pt-3 border border-white">
                            <div class="card col-11 mx-auto bg-primary text-white">
                                <div class="d-flex">
                                    <div class="col-2 text-center">伝票No.</div>
                                    <div class="col-3 text-center">預り日</div>
                                    <div class="col-3 text-center">顧客名</div>
                                    <div class="col-2 text-center">金額</div>
                                    <div class="col-2 text-center">支払<span class="px-1">|</span>渡し</div>
                                </div>
                            </div>
                        </div>
                        <div class="card col-11 mx-auto my-2 py-2 odlist-orders-order"
                            v-for="(order, index) in orders"
                            :key="order.id"
                            @click="select(index)"
                            :class="{ 'odlist-unselected': order.id !== selectedId, 'odlist-selected': order.id === selectedId }">
                            <div class="d-flex">
                                <div class="col-2 text-center">{{ order.id }}</div>
                                <div class="col-3 text-center">{{ dateFormater(order.created_at, 'YYYY/MM/DD') }}</div>
                                <div class="col-3 text-center">{{ customer.name }}</div>
                                <div class="col-2 text-center">{{ order.amount.toLocaleString() }}円</div>
                                <div class="col-2 text-center">
                                    <i class="fa-solid fa-check text-success col-5" v-if="order.paid_at !== null"></i>
                                    <i class="fa-solid fa-xmark text-danger col-5" v-else></i>
                                    <span class="col-2 text-center">|</span>
                                    <i class="fa-solid fa-check text-success col-5" v-if="order.handed_at !== null"></i>
                                    <i class="fa-solid fa-xmark text-danger col-5" v-else></i>
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
                <div class="card card-border col-12 odlist-operation">
                    <div class="card-header text-center fw-bold">
                        操作
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-around">
                            <button class="odlist-operation-btn odlist-operation-btn-active text-white mt-2 bg-secondary" onclick="location.href='/'">
                                <div class="odlist-operation-btn-icon">
                                    <i class="fa-solid fa-house"></i>
                                </div>
                                <div class="odlist-operation-btn-label text-center">
                                    戻る
                                </div>
                            </button>
                            <button class="odlist-operation-btn text-white mt-2 ls-green odlist-operation-btn-active"
                                @click="dispSearch = true">
                                <div class="odlist-operation-btn-icon">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                                <div class="odlist-operation-btn-label text-center">
                                    検索
                                </div>
                            </button>
                            <button class="odlist-operation-btn text-white mt-2 uh-orange"
                                :class="{ 'odlist-operation-btn-inactive': selectedId === '', 'odlist-operation-btn-active': selectedId !== '' }"
                                :disabled="selectedId === ''"
                                @click="dispDetail = true">
                                <div class="odlist-operation-btn-icon">
                                    <i class="fa-solid fa-list"></i>
                                </div>
                                <div class="odlist-operation-btn-label">
                                    詳細
                                </div>
                            </button>
                            <button class="odlist-operation-btn bg-danger text-white mt-2"
                                :class="{ 'odlist-operation-btn-inactive': selectedId === '', 'odlist-operation-btn-active': selectedId !== ''}"
                                :disabled="selectedId === ''"
                                @click="sendDeleteReq()"
                                >
                                <div class="odlist-operation-btn-icon">
                                    <i class="fa-solid fa-trash-can"></i>
                                </div>
                                <div class="odlist-operation-btn-label">
                                    取り消し
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card card-border position-relative mt-2 odlist-detail">
                    <div class="col-12 mb-2 bg-white position-sticky odlist-detail-column pt-3 border border-white">
                        <div class="card col-11 mx-auto bg-primary text-white">
                            <div class="d-flex">
                                <div class="col-3 text-center">タグNo.</div>
                                <div class="col-5 text-center">商品名</div>
                                <div class="col-2 text-center">値段</div>
                                <div class="col-2 text-center">渡し</div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-11 mx-auto my-2 py-2 odlist-detail-clothes"
                        v-for="item in items"
                        :key="item.id">
                        <div class="d-flex">
                            <div class="col-3 text-center">{{ item.tag }}</div>
                            <div class="col-5 text-center">{{ item.name }}</div>
                            <div class="col-2 text-center">{{ item.price.toLocaleString() }}円</div>
                            <div class="col-2 text-center">
                                <i class="fa-solid fa-check text-success" v-if="item.handed_at !== null"></i>
                                <i class="fa-solid fa-xmark text-danger" v-else></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <search-modal
            @close="close"
            @search="displaySearchResult"
            :conditions="conditions"
            v-if="dispSearch"
        ></search-modal>
        <detail-modal
            @close="close"
            :customer="customer"
            :order="selectedOrder"
            v-if="dispDetail"
        ></detail-modal>
    </div>
</template>

<script>
import moment from "moment";
import SearchModal from './Modals/HistorySearchModalComponent';
import DetailModal from './Modals/HistoryDetailModalComponent';

export default ({
    props: {
        customer: {
            required: true
        },
        initial_orders: {
            required: true
        },
        token: {
            Type: String,
            required: true,
        }
    },
    data() {
        return {
            dispSearch: false,
            dispDetail: false,
            orders: this.initial_orders,
            items: [],
            conditions: [],
            selectedId: '',
            selectedOrder: [],
        }
    },
    components: {
        'search-modal': SearchModal,
        'detail-modal': DetailModal,
    },
    methods: {
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
        },
        close: function() {
            this.dispSearch = false;
            this.dispDetail = false;
        },
        select: function(index) {
            this.items = this.orders[index]['items'];
            this.items.splice();
            this.selectedId = this.orders[index].id;
            this.selectedOrder = this.orders[index];
        },
        displaySearchResult: async function(conditions) {
            let result = await this.search(conditions);
            this.orders = result.orders;
            this.conditions = result.conditions;
            this.selectedId = '';
            this.selectedOrder = [];
            this.items = [];
            this.close();
        },
        search: async function(conditions) {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            return await axios.post('/api/order/search', {
                customer_id: this.customer.id,
                conditions: conditions,
            })
            .then(function (response) {
                return response.data;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        sendDeleteReq: async function() {
            let confirm = window.confirm('こちらの伝票を削除します。よろしいですか？');
            if (confirm) {
                await this.delete();
            }
        },
        delete: async function() {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            return await axios.post('/api/order/delete', {
                customer_id: this.customer.id,
                order_id: this.selectedId,
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
