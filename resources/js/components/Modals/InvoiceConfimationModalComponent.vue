<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window border border-4 border-dark" style="width: 40vw;">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="form-group">
                            <div class="col-12 fs-24">請求書に計上します。</div>
                            <div class="col-12 fs-20">お渡し日時を編集する場合は下記のフォームに入力してください。</div>
                            <div class="col-12 mt-4 fs-22">
                                <input type="date" class="col-10 form-control" v-model="date">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fs-20" @click="$emit('close')">閉じる</button>
                        <a class="btn btn-primary fs-20" @click="$emit('send', date)">OK</a>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    data() {
        return {
            date: '',
        }
    },

}
</script>