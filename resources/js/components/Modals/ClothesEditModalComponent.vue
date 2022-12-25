<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="menu-create-modal-label">衣類情報入力</h5>
                    </div>
                    <form wire:submit.prevent="save">
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
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="$emit('close')">閉じる</button>
                            <button type="submit" class="btn btn-primary" @click="$emit('update', name, name_kana, price, tag_count)">更新</button>
                        </div>
                    </form>
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
        }
    },
    mounted() {
        this.name = this.info.name;
        this.name_kana = this.info.name_kana;
        this.price = this.info.price;
        this.tag_count = this.info.tag_count;
    },
}
</script>