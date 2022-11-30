<template>
    <div class="col-3 mt-2 col-3-custom">
        <div class="card px-2 history-card"
            @click="dispDetail = true"
            :class="{ 'history-card-uh': order.handed_at === null }">
            <div class="col-12 text-nowrap overflow-hidden"
                v-for="item in limit"
                :key="item.key"
            >{{ item.name }}</div>
            <div class="col-12 text-nowrap overflow-hidden">全{{ order.count }}点</div>
            <div class="col-12 text-nowrap overflow-hidden">{{ order.amount.toLocaleString() }}円</div>
            <div class="col-12 text-nowrap overflow-hidden" v-if="order.handed_at === null">未渡し</div>
            <div class="col-12 text-nowrap overflow-hidden" v-else>お渡し済み</div>
        </div>
        <detail-modal
            @close="close"
            :customer="customer"
            :order="order"
            v-if="dispDetail"
        ></detail-modal>
    </div>
</template>

<script>
import DetailModal from './Modals/HistoryDetailModalComponent';

export default ({
    props: {
        customer: {
            required: true
        },
        order: {
            required: true
        }
    },
    data() {
        return {
            dispDetail: false
        }
    },
    components: {
        'detail-modal': DetailModal,
    },
    methods: {
        close: function() {
            this.dispDetail = false;
        }
    },
    computed: {
        limit() {
            return this.order.items.slice(0, 3);
        }
    }
})
</script>
