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
                            <div class="col-8" v-if="conditions.length !== 0">
                                <div class="col-12 d-flex"
                                    v-for="(condition, name, _) in conditions"
                                    :key="name">
                                    <div class="col-4">{{ name }} </div>
                                    <div class="col-8">{{ condition }}</div>
                                </div>
                            </div>
                            <div class="col-8" v-else>
                                <div class="col-12">
                                    <div class="col-4">なし</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body col-12 modal-cs-search">
                            <div class="col-12 input-group d-flex my-3">
                                <label for="order_id" class="col-4 fw-bold modal-cs-search-label-i">伝票番号</label>
                                <div class="col-3 px-1">
                                    <input type="number" name="order_id" id="order_id" class="w-100" v-model="order_id">
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex my-3">
                                <label for="before" class="col-4 fw-bold modal-cs-search-label">期間</label>
                                <div class="col-8 px-1">
                                    <input type="date" name="after" id="after" v-model="after">
                                    <span class="px-2">〜</span>
                                    <input type="date" name="before" id="before" v-model="before">
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex my-3">
                                <label for="tag" class="col-4 fw-bold modal-cs-search-label-i">タグ</label>
                                <div class="col-4 d-flex px-1">
                                    <input class="col-4" type="number" name="tag" id="tag" v-model="tag_head" min="1"  max="10">
                                    <span class="px-2">-</span>
                                    <input class="col-7" type="number" name="tag" id="tag" v-model="tag_body" min="0" max="999">
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex my-3">
                                <label for="has_paid" class="col-4 fw-bold modal-cs-search-label">お支払い済み</label>
                                <div class="col-8 d-flex px-1">
                                    <div class="col-4">
                                        <input type="radio" id="has_paid_none" v-model="has_paid" value="neither">
                                        <label for="has_paid_none">指定なし</label>
                                    </div>
                                    <div class="col-4">
                                        <input type="radio" id="has_paid_false" v-model="has_paid" value="unpaid">
                                        <label for="has_paid_false">未収</label>
                                    </div>
                                    <div class="col-4">
                                        <input type="radio" id="has_paid_true" v-model="has_paid" value="paid">
                                        <label for="has_paid_true">お支払済み</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex my-3">
                                <label for="has_handed" class="col-4 fw-bold modal-cs-search-label">お渡し済み</label>
                                <div class="col-8 d-flex px-1">
                                    <div class="col-4">
                                        <input type="radio" id="has_handed_none" v-model="has_handed" value="neither">
                                        <label for="has_handed_none">指定なし</label>
                                    </div>
                                    <div class="col-4">
                                        <input type="radio" id="has_handed_false" v-model="has_handed" value="unhanded">
                                        <label for="has_handed_false">未渡し</label>
                                    </div>
                                    <div class="col-4">
                                        <input type="radio" id="has_handed_true" v-model="has_handed" value="handed">
                                        <label for="has_handed_true">お渡し済み</label>
                                    </div>
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
        conditions: {
            require: true
        }
    },
    data() {
        return {
            order_id: '',
            after: '',
            before: '',
            tag_head: '',
            tag_body: '',
            has_paid: 'neither',
            has_handed: 'neither',

            columnJp: {
                'order_id': '伝票番号',
                'term': '期間',
                'tag': 'タグ',
                'has_paid': 'お支払い済み',
                'has_handed': 'お渡し済み',
            },
        }
    },
    methods: {
        send: function() {
            let tag = ''
            if (this.tag_head !== '' && this.tag_body !== '') {
                tag = this.tag_head + '-' + ('000' + this.tag_body).slice(-3);
            }
            let condition = {
                'order_id': this.order_id,
                'after': this.after,
                'before': this.before,
                'tag': tag,
                'has_paid': this.has_paid,
                'has_handed': this.has_handed,
            };

            this.$emit('search', condition);
        },
    },
})
</script>
