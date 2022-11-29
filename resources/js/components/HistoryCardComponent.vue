<template>
    <div class="col-3 mt-2 col-3-custom px-1">
        <div class="card  history-card"
            @click="dispDetail = true">
            <div class="col-12 text-nowrap overflow-hidden"
                v-for="item in limit"
                :key="item.key"
            >{{ item.name }}</div>
            <div class="col-12 text-nowrap overflow-hidden">全4点</div>
            <div class="col-12 text-nowrap overflow-hidden">3000円</div>
            <div class="col-12 text-nowrap overflow-hidden">未渡し</div>
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
