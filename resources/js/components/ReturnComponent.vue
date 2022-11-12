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
                    未渡し一覧
                </div>
                <div class="card position-relative unhanded-orders">
                    <div class="col-12 mb-2 bg-white position-sticky unhanded-orders-column pt-3 border border-white">
                        <div class="card col-11 mx-auto bg-primary text-white">
                            <div class="d-flex">
                                <div class="col-2 text-center">伝票No.</div>
                                <div class="col-3 text-center">預り日</div>
                                <div class="col-5 text-center">顧客名</div>
                                <div class="col-2 text-center">支払い済</div>
                            </div>
                        </div>
                    </div>
                    <div class="card col-11 mx-auto my-2 py-2 unhanded-orders-order"
                        v-for="(order, index) in orders"
                        :key="index"
                        :class="{ 'unhanded-selected': index === selectedOrder, 'unhanded-unselected': index !== selectedOrder }"
                        @click="selectOrder(index)">
                        <div class="d-flex">
                            <div class="col-2 text-center">{{ order.id }}</div>
                            <div class="col-3 text-center">{{ order.created_at }}</div>
                            <div class="col-5 text-center">{{ customer.name }}</div>
                            <div class="col-2 text-center">
                                <i :class="{ 'fa-solid fa-check text-success': order.paid_at !== null, 'fa-solid fa-xmark text-danger': order.paid_at === null }"></i>
                            </div>
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
            <div class="card col-12 unhanded-operation">
                <div class="card-header text-center fw-bold">
                    操作
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-around">
                        <a href="/">
                            <button class="unhanded-operation-btn text-white mt-2 bg-secondary" >
                                <div class="unhanded-operation-btn-icon">
                                    <i class="fa-solid fa-house"></i>
                                </div>
                                <div class="unhanded-operation-btn-label text-center">
                                    戻る
                                </div>
                            </button>
                        </a>
                        <button class="unhanded-operation-btn text-white mt-2 uh-orange unhanded-operation-btn-inactive" :disabled="true">
                            <div class="unhanded-operation-btn-icon">
                                <i class="fa-solid fa-money-bill"></i>
                            </div>
                            <div class="unhanded-operation-btn-label text-center">
                                入金
                            </div>
                        </button>
                        <button class="unhanded-operation-btn text-white mt-2 uh-green"
                            @click="allSelected ? deselection(): selectAll()"
                            :class="{ 'unhanded-operation-btn-inactive': selectedOrder === '' }"
                            :disabled="selectedOrder === ''">
                            <div class="unhanded-operation-btn-icon">
                                <i class="fa-regular fa-square" v-if="allSelected"></i>
                                <i class="fa-regular fa-square-check" v-else></i>
                            </div>
                            <div class="unhanded-operation-btn-label">
                                {{ allSelected ? '選択解除': '全選択' }}
                            </div>
                        </button>
                        <button class="unhanded-operation-btn bg-primary text-white mt-2"
                            @click="send()"
                            :class="{ 'unhanded-operation-btn-inactive': selectedItems.length === 0 }"
                            :disabled="selectedItems.length === 0">
                            <div class="unhanded-operation-btn-icon">
                                <i class="fa-solid fa-shirt"></i>
                            </div>
                            <div class="unhanded-operation-btn-label">
                                お渡し
                            </div>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card position-relative mt-2 unhanded-detail">
                <div class="col-12 mb-2 bg-white position-sticky unhanded-detail-column pt-3 border border-white">
                    <div class="card col-11 mx-auto bg-primary text-white">
                        <div class="d-flex">
                            <div class="col-3 text-center">タグNo.</div>
                            <div class="col-7 text-center">商品名</div>
                            <div class="col-2 text-center">値段</div>
                        </div>
                    </div>
                </div>
                <template v-if="items !== []">
                    <div class="card col-11 mx-auto my-2 py-2 unhanded-detail-clothes"
                        v-for="item in items"
                        :key="item.id"
                        :class="{
                            'unhanded-handed': item.handed_at !== null,
                            'unhanded-selected': selectedItems.indexOf(item.id) !== -1,
                            'unhanded-unselected': selectedItems.indexOf(item.id) === -1
                            }"
                        @click="item.handed_at === null ? switchItem(item.id) : null">
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
    </div>
</template>

<script>
import CustomerInfo from './CustomerInfoComponent';
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
    },
    data() {
        return {
            selectedOrder: '',
            selectedItems: [],
            items: [],
            allSelected: false,
        }
    },
    methods: {
        selectOrder: function(id) {
            this.selectedOrder = id;
            this.selectedItems = [];
            this.items = [];
            for (const item of this.orders[id].items) {
                this.items.push(item);
            }
        },
        switchItem: function(id) {
            let index = this.selectedItems.indexOf(id)
            if (index === -1) {
                this.selectedItems.push(id);
            } else {
                this.selectedItems.splice(index, 1);
            }
            this.isSelectedAll();
        },
        selectAll: function () {
            this.selectedItems = [];
            this.selectedItems = this.orders[this.selectedOrder].items.map(function(item) {
                if (item.handed_at === null) {
                    return item.id;
                }
            }).filter(e => typeof e !== 'undefined');
            this.allSelected = true;
            console.log('selectAll')
        },
        deselection: function () {
            this.selectedItems = [];
            this.allSelected = false;
            console.log('deselection')
        },
        isSelectedAll: function() {
            let base = this.orders[this.selectedOrder].items.map(function(item) {
                if (item.handed_at === null) {
                    return item.id;
                }
            }).filter(e => typeof e !== 'undefined');

            let diff = base.filter(i => this.selectedItems.indexOf(i) == -1);

            if (diff.length === 0) {
                this.allSelected = true;
            } else {
                this.allSelected = false;
            }
        },
        send: function() {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            axios.post('api/return/update', {
                items: this.selectedItems
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
