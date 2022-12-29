<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window border border-4 border-dark">
                <div class="modal-content" style="width: 60vw;">
                    <div class="modal-header cs-field">
                        <h5 class="modal-title fs-26 fw-bold">CS情報</h5>
                    </div>
                    <div class="modal-body">
                        <div class="col-12" v-for="row in info" :key="row.id">
                            <div class="col-12 d-flex fs-20">
                                <div class="col-8">{{ row.information }}</div>
                                <div class="col-3 text-center">{{ dateFormater(row.created_at, 'YYYY/MM/DD') }}</div>
                                <div class="col-1 mt-1">
                                    <div
                                        class="rounded bg-danger text-center text-white mx-auto"
                                        style="height:26px;line-height:1.4;"
                                        @click="$emit('delete', row.id)">
                                        削除
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="col-12 d-flex">
                            <textarea class="col-10 fs-20" v-model="inputInformation"></textarea>
                            <div class="col-2">
                                <button class="col-10 ms-3 btn btn-primary fs-20" @click="$emit('add', inputInformation);inputInformation=''">追加</button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fs-20" @click="$emit('close');">閉じる</button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import moment from "moment";
export default {
    props: {
        info: {
            required: false
        },
        token: {
            required: true
        }
    },
    data() {
        return {
            inputInformation: ''
        }
    },
    methods: {
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
        },
    }
}
</script>