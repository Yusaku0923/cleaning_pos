<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window border border-4 border-dark">
                <div class="modal-content">
                    <div class="card modal-iv">
                        <div class="card-header col-12 modal-iv-label">操作</div>
                        <div class="card-body col-12 modal-iv-list">
                            <div
                                class="card col-10 mx-auto px-3 py-1 text-center modal-iv-list-btn"
                                :class="{'modal-iv-list-btn-succeeded': CO_done}"
                                v-if="isAbleToCarryOver()"
                                @click="CO_send()">
                                {{ CO_message }}
                            </div>
                            <div
                                class="card col-10 mx-auto mt-2 px-3 py-1 text-center modal-iv-list-btn"
                                :class="{ 'modal-iv-list-btn-succeeded': AC_done}"
                                v-if="isAbleToAlignCutoffDate()"
                                @click="AC_send()">
                                {{ AC_message }}
                            </div>
                        </div>
                        <div class="col-12 card-footer py-3 d-flex justify-content-end">
                            <div class="col-3 text-end">
                                <button class="px-3 py-1 modal-iv-btn modal-iv-btn-back" @click="$emit('close')">閉じる</button>
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
        invoices: {
            require: true
        }
    },
    data() {
        return {
            // CO: Carry Over
            CO_message: '選択中の請求書を繰り越し',
            CO_done: false,
            // AC: Align Cutoff
            AC_message: '選択中の請求書を現在の締日に揃える',
            AC_done: false,
        }
    },
    methods: {
        isAbleToCarryOver: function() {
            let onlyValid = false;
            // 管理めんどいから1個に限定
            if (Object.keys(this.invoices).length !== 1) {
                return false;
            }

            for (const key in this.invoices) {
                if(this.invoices.hasOwnProperty(key)) {
                    if (this.invoices[key].carry_over_id === null) {
                        onlyValid = true;
                    }
                }
            }

            return onlyValid;
        },
        isAbleToAlignCutoffDate: function() {
            let onlyValid = false;
            // 管理めんどいから1個に限定
            if (Object.keys(this.invoices).length !== 1) {
                return false;
            }

            for (const key in this.invoices) {
                if(this.invoices.hasOwnProperty(key)) {
                    if (Boolean(this.invoices[key].is_mismatch_cutoff_date)) {
                        onlyValid = true;
                    }
                }
            }

            return onlyValid;
        },
        CO_send: function() {
            if (this.isAbleToCarryOver() && !this.CO_done) {
                this.$emit('co_send');
                this.CO_message = '繰越処理が完了しました';
                this.CO_done = true;
            }
        },
        AC_send: function() {
            if (this.isAbleToAlignCutoffDate() && !this.AC_done) {
                this.$emit('ac_send');
                this.AC_message = '締日整列処理が完了しました';
                this.AC_done = true;
            }
        },
    },
})
</script>
