<template>
    <div class="col-12 py-3 mx-auto d-flex justify-content-between">
        <div class="category-bar col-3 border border-secondary">
            <div class="card py-5 px-2 border border-secondary"
                v-for="(category, index) in categories"
                :key="category.id"
                v-on:click="change(index)"
                v-bind:class="{'active': isActive === index}"
            >
                {{ category.name }}
            </div>
        </div>

        <div class="clothes-bar col-5 border border-secondary">
            <template v-for="(category, index) in categories">
                <template v-if="isActive === index">
                    <div class="card border border-secondary clothes-card position-relative p-2"
                        v-for="clothes in category.clothes"
                        :key="clothes.id"
                        v-on:click="add(clothes)">
                        <div class="">
                            {{ clothes.name }}
                        </div>
                        <div class="position-absolute bottom-0">
                            {{ clothes.price }} 円
                        </div>
                    </div>
                </template>
            </template>
        </div>

        <div class="slip-bar col-4 position-relative">
            <div class="col-12 py-3 px-2 border border-secondary bg-white">
                計：5点
            </div>

            <div class="order-list">
                <div class="col-12 py-3 px-2 border border-secondary bg-white"
                    v-for="item in order"
                    :key="item.id">
                    <div class="col-12 px-2 order-title">{{ item.name }}</div>
                    <div class="col-12 mt-2 d-flex justify-content-between order-detail">
                        <div class="col-5 d-flex justify-content-between">
                            <button class="card border border-primary text-primary p-2">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <button class="card border border-primary text-primary p-2">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                            <div class="text-primary order-text">
                                {{ item.count }} 点
                            </div>
                        </div>
                        <div class="col-5 order-text text-end">
                            {{ item.count * item.price }} 円
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 py-4 px-2 bg-primary text-white position-absolute bottom-0">
                5000円 お会計へ
            </div>

        </div>
    </div>
</template>

<script>
export default ({
    props: {
        categories: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            categories: this.categories,
            isActive: '1',
            order: [],
        }
    },
    methods: {
        change: function (num) {
            this.isActive = num;
        },
        add: function(clothes) {
            if (this.order[clothes.id]) {
                clothes.count = this.order[clothes.id].count + 1;
                this.$set(this.order[clothes.id], 'count', this.order[clothes.id].count + 1);
                // this.order.splece(index, 1, clothes);
            } else {
                clothes.count = 1;
                this.$set(this.order, clothes.id, clothes);
            }
        },
        increase: function (index) {

        }
    }
});
</script>