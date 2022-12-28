<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window" style="width: 35vw;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="menu-create-modal-label">衣類情報入力</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group col-12 mx-auto mb-2">
                            <label for="name" class="col-form-label">衣類名</label>
                            <input type="text" class="form-control form-control-lg" id="name" placeholder="" v-model="name"/>
                        </div>
                        <div class="form-group col-12 mx-auto mb-2">
                            <label for="name_kana" class="col-form-label">衣類名(カナ)</label>
                            <input type="text" class="form-control form-control-lg"  id="name_kana" placeholder="" v-model="name_kana"/>
                        </div>
                        <div class="form-group col-12 mx-auto">
                            <label for="price" class="col-form-label">料金</label>
                            <input type="number" class="form-control form-control-lg" id="price" placeholder="" v-model="price"/>
                        </div>
                        <div class="form-group col-12 mx-auto">
                            <label for="tag_count" class="col-form-label">タグ枚数</label>
                            <input type="number" class="form-control form-control-lg" id="tag_count" placeholder="" v-model="tag_count"/>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <button
                                type="button"
                                class="btn btn-danger text-white"
                                v-if="mode === 'edit'"
                                @click="$emit('delete')">削除</button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" @click="$emit('close')">閉じる</button>
                            <button
                                type="submit"
                                class="btn"
                                :class="{'btn-success text-white': (mode === 'create'), 'btn-primary': (mode === 'edit')}"
                                @click="send()"
                            >{{ label }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    props: {
        mode: {
            require: true
        },
        info: {
            required: true
        }
    },
    data() {
        return {
            name: '',
            name_kana: '',
            price: '',
            tag_count: '',
            label: '',
        }
    },
    mounted() {
        if (this.mode === 'edit') {
            this.name = this.info.name;
            this.name_kana = this.info.name_kana;
            this.price = this.info.price;
            this.tag_count = this.info.tag_count;

            this.label = '更新';
        } else if (this.mode === 'create') {
            this.label = '作成';
        }
    },
    methods: {
        send: function() {
            if (this.mode === 'create') {
                this.$emit('create', this.name, this.name_kana, this.price, this.tag_count);
            } else if (this.mode === 'edit') {
                this.$emit('update', this.name, this.name_kana, this.price, this.tag_count);
            }
        }
    }
}
</script>