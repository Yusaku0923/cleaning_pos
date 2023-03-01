<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window" style="width: 35vw;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="menu-create-modal-label">カテゴリー情報入力</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group col-12 mx-auto mb-2">
                            <label for="name" class="col-form-label">カテゴリー名</label>
                            <input type="text" class="form-control form-control-lg" id="name" placeholder="" v-model="name"/>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" @click="$emit('close')">閉じる</button>
                            <button
                                type="submit"
                                class="btn btn-success text-white"
                                @click="send()"
                            >追加</button>
                        </div>
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
            name: '',
        }
    },
    // mounted() {
    //     if (this.mode === 'edit') {
    //         this.name = this.info.name;
    //         this.name_kana = this.info.name_kana;
    //         this.price = this.info.price;
    //         this.tag_count = this.info.tag_count;

    //         this.label = '更新';
    //     } else if (this.mode === 'create') {
    //         this.label = '作成';
    //     }
    // },
    methods: {
        send: function() {
            this.$emit('create', this.name);
        }
    }
}
</script>