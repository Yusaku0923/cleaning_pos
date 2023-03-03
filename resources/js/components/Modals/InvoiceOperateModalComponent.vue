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
                                :class="{ 'modal-iv-list-btn-disabled': isNotAbleToCarryOver(), 'modal-iv-list-btn-succeeded': CO_done}"
                                @click="send()">
                                {{ CO_message }}
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
            CO_message: '選択中の請求書を繰り越し',
            CO_done: false,
        }
    },
    methods: {
        isNotAbleToCarryOver: function() {
            let includeInvalid = false;
            for (const key in this.invoices) {
                if(this.invoices.hasOwnProperty(key)) {
                    if (this.invoices[key].carry_over_id != null) {
                        includeInvalid = true;
                    }
                }
            }

            return !Object.keys(this.invoices).length || includeInvalid;
        },
        send: function() {
            if (!this.isNotAbleToCarryOver() && !this.CO_done) {
                this.$emit('send');
                this.CO_message = '繰越処理が完了しました';
                this.CO_done = true;
            }
        },
    },
})
</script>
