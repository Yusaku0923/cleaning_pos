<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window">
                <div class="modal-content">
                    <div class="card modal-cs p-3">
                        <div class="card-header col-12 modal-cs-label">検索</div>
                        <div class="card-header col-12 modal-cs-current d-flex">
                            <div class="col-4 fw-bold">
                                現在の検索項目
                            </div>
                            <div class="col-8">
                                <div class="col-12 d-flex">
                                    <div class="col-4">伝票番号</div>
                                    <div class="col-8">20222</div>
                                </div>
                                <div class="col-12 d-flex">
                                    <div class="col-4">期間</div>
                                    <div class="col-8">2022/09/23 〜 2022/10/10</div>
                                </div>
                                <div class="col-12 d-flex">
                                    <div class="col-4">タグ</div>
                                    <div class="col-8">10-100</div>
                                </div>
                                <div class="col-12 d-flex">
                                    <div class="col-4">お支払い済み</div>
                                    <div class="col-8">はい</div>
                                </div>
                                <div class="col-12 d-flex">
                                    <div class="col-4">お渡し済み</div>
                                    <div class="col-8">はい</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body col-12 modal-cs-search">
                            <div class="col-12 input-group d-flex mb-3">
                                <label for="order_id" class="col-4 fw-bold modal-cs-search-label">伝票番号</label>
                                <div class="col-3">
                                    <input type="number" name="order_id" id="order_id" v-model="order_id">
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex mb-3">
                                <label for="before" class="col-4 fw-bold modal-cs-search-label">期間</label>
                                <div class="col-8">
                                    <input type="date" name="before" id="before" v-model="before">
                                    <span class="px-2">〜</span>
                                    <input type="date" name="after" id="after" v-model="after">
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex mb-3">
                                <label for="tag" class="col-4 fw-bold modal-cs-search-label">タグ</label>
                                <div class="col-4 d-flex">
                                    <input class="col-4" type="number" name="tag" id="tag" v-model="tag_head" min="1"  max="10">
                                    <span class="px-2">-</span>
                                    <input class="col-7" type="number" name="tag" id="tag" v-model="tag_body" min="0" max="999">
                                </div>
                            </div>
                            <div class="col-12 input-group d-flex mb-3 modal-cs-search-label">
                                <label for="has_paid" class="col-4 fw-bold">お支払い済み</label>
                                <div class="col-8 d-flex">
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
                            <div class="col-12 input-group d-flex mb-3 modal-cs-search-label">
                                <label for="has_handed" class="col-4 fw-bold">お渡し済み</label>
                                <div class="col-8 d-flex">
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
                                        <label for="has_handed_true">お渡し済</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 modal-cs-btn card-footer pt-4 d-flex justify-content-end">
                            <div class="col-2 text-end">
                                <button class="btn px-3 py-1 modal-cs-btn-back">戻る</button>
                            </div>
                            <div class="col-2 text-end">
                                <button class="btn px-3 py-1 modal-cs-btn-send" @click="send()">送信</button>
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

    },
    data() {
        return {
            order_id: '',
            before: '',
            after: '',
            tag: '',
            has_paid: 'neither',
            has_handed: 'neither',
        }
    },
    method: {
        send: async function() {

        },
        search: async function() {

        }
    }
})
</script>
