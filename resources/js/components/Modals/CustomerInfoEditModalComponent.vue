<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close')">
            <div class="modal-window border border-4 border-dark">
                <div class="modal-content">
                    <div class="col-12" style="width: 80vw;">
                        <div v-for="row in info" :key="row.id">{{ row.information }}</div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    props: {
        info: {
            required: false
        },
        token: {
            required: true
        }
    }
}
</script>