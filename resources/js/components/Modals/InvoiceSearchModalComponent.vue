<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window border border-4 border-dark">
                <div class="modal-content">
                    <div class="card modal-cs">
                        <div class="card-header col-12 modal-cs-label">検索</div>
                        <div class="card-header col-12 modal-cs-current d-flex">
                            <div class="col-4 fw-bold">
                                現在の検索項目
                            </div>
                            <div class="col-8">
                                <div class="col-12 d-flex" v-if="cutoff_date !== 0">
                                    <div class="col-4">締日</div>
                                    <div class="col-8">{{ cutoffDate == 99 ? '末日': cutoffDate + '日' }}</div>
                                </div>
                                <div class="col-12 d-flex" v-if="target_month !== ''">
                                    <div class="col-4">対象月</div>
                                    <div class="col-8">{{ target_month }}</div>
                                </div>
                                <div class="col-12 d-flex" v-if="customer_name !== ''">
                                    <div class="col-4">名前</div>
                                    <div class="col-8">{{ customer_name }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body col-12 modal-cs-search">
                            <div class="col-12 input-group d-flex my-3">
                                <label for="cutoff_date" class="col-4 fw-bold modal-cs-search-label-i">締日</label>
                                <div class="col-3 px-1">
                                    <select class="w-100" name="cutoff_date" id="cutoff_date" v-model="cutoffDate">
                                        <option
                                        v-for="(date, key, _) in date_select"
                                        :key="key"
                                        :value="key">{{ date }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex my-3">
                                <label for="target_month" class="col-4 fw-bold modal-cs-search-label">対象月</label>
                                <div class="col-8 px-1">
                                    <input  type="month" name="target_month" id="target_month" v-model="targetMonth">
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex my-3">
                                <label for="customer_name" class="col-4 fw-bold modal-cs-search-label">名前</label>
                                <div class="col-8 px-1">
                                    <input type="text" name="customer_name" id="customer_name" v-model="customerName">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 card-footer py-3 d-flex justify-content-end">
                            <div class="col-3 text-end">
                                <button class="px-3 py-1 modal-cs-btn modal-cs-btn-back" @click="$emit('close')">閉じる</button>
                            </div>
                            <div class="col-2 text-end">
                                <button class="px-3 py-1 modal-cs-btn modal-cs-btn-send" @click="send()">検索</button>
                            </div>
                        </div>
                    </div>
                    <!-- タグ番号のリストとか作るとよいかも -->
                </div>
            </div>
        </div>
    </transition>
</template>

<script>

export default ({
    props: {
        cutoff_date: {
            required: true
        },
        target_month: {
            required: true
        },
        customer_name: {
            required: true
        },
    },
    data() {
        return {
            cutoffDate: this.cutoff_date,
            targetMonth: this.target_month,
            customerName: this.customer_name,
            date_select: {
                0: '-',
                1: 1,
                2: 2,
                3: 3,
                4: 4,
                5: 5,
                6: 6,
                7: 7,
                8: 8,
                9: 9,
                10: 10,
                11: 11,
                12: 12,
                13: 13,
                14: 14,
                15: 15,
                16: 16,
                17: 17,
                18: 18,
                19: 19,
                20: 20,
                21: 21,
                22: 22,
                23: 23,
                24: 24,
                25: 25,
                26: 26,
                27: 27,
                28: 28,
                99: '末日'
            }
        }
    },
    methods: {
        send: function() {
            let condition = {
                'cutoff_date': this.cutoffDate,
                'target_month': this.targetMonth,
                'customer_name': this.customerName,
            };

            this.$emit('search', condition);
        },
    }
})
</script>
