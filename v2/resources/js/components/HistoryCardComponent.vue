<template>
    <div class="col-3 col-3-custom">
        <div class="card px-2 history-card fs-16"
            @click="dispDetail = true"
            :class="{ 'history-card-uh': current.handed_at === null }">
            <div class="col-12 text-nowrap overflow-hidden"
                v-for="item in limit"
                :key="item.key"
            >{{ item.name }}</div>
            <div class="col-12 text-nowrap overflow-hidden">全{{ current.count }}点</div>
            <div class="col-12 text-nowrap overflow-hidden">{{ current.amount.toLocaleString() }}円</div>
            <div class="col-12 text-nowrap overflow-hidden" v-if="current.handed_at === null">未渡し</div>
            <div class="col-12 text-nowrap overflow-hidden" v-else>お渡し済み</div>
        </div>
        <detail-modal
            @close="close"
            @updateTag="updateTag"
            :customer="customer"
            :order="current"
            v-if="dispDetail"
        ></detail-modal>
    </div>
</template>

<script>
import DetailModal from './Modals/HistoryDetailModalComponent.vue';

export default {
    props: {
        customer: {
            required: true
        },
        order: {
            required: true
        },
    },
    data() {
        return {
            dispDetail: false,
            // ホームの Blade から渡る注文は再描画で作り直される素のオブジェクトなので、
            // タグを直したとき画面に残るよう手元に reactive な写しを持つ (旧は props を直接書き換えていた)
            current: JSON.parse(JSON.stringify(this.order)),
        }
    },
    components: {
        'detail-modal': DetailModal,
    },
    methods: {
        close: function() {
            this.dispDetail = false;
        },
        updateTag: async function(id, tag, onSaved) {
            console.log(id);
            console.log(tag);
            return await axios.post('/api/order/update_tag', {
                id: id,
                tag: tag,
            })
            .then(function (response) {
                if (onSaved) onSaved();
                return response.data;
            })
            .catch(function (error) {
                // 保存できなかったことを ErrorLogger (console.error を拾う) に残す
                console.error('タグを更新できませんでした', id, tag, error && error.response ? error.response.data : error);
                return;
            });
        }
    },
    computed: {
        limit() {
            return this.current.items.slice(0, 3);
        },
    }
}
</script>
