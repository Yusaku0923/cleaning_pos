<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window border border-4 border-dark" style="width: 40vw;">
                <div class="modal-content">
                    <div class="modal-header manager-field">
                        <h5 class="modal-title fs-26 fw-bold" id="manager-select-modal-label">担当者変更</h5>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <div
                                class="card p-3 mb-3 mx-auto col-10 fs-24 text-start border border-3"
                                v-for="manager in managers"
                                :key="manager.id"
                                :class="{ 'border-primary bg-primary text-white': selectedManager == manager.id,
                                'border-secondary': selectedManager != manager.id }"
                                @click="select(manager.id)"
                            >
                                {{ manager.name }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fs-20" @click="$emit('close')">閉じる</button>
                        <a class="btn btn-primary fs-20" :href="link">更新</a>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    props: {
        managers: {
            required: true
        },
        selected: {
            required: false
        }
    },
    data() {
        return {
            link: '/',
            selectedManager: ''
        }
    },
    mounted() {
        this.selectedManager = this.selected;
    },
    methods: {
        select: function(id) {
            // 選択中の顧客 (?m=&c=) を渡す。同じ担当者なら顧客を引き継ぐ (旧: セッションの顧客が残る)
            this.link = '/manager/update/' + id + window.location.search;
            this.selectedManager = id;
        }
    }
}
</script>