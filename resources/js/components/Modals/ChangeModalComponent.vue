<template>
    <transition name="modal" appear>
        <div class="modal modal-overlay" @click.self="$emit('close', 5)">
            <div class="modal-window">
                <div class="modal-content">
                    <div class="col-6 card modal-ch p-3">
                        <div class="col-12 modal-ch-label">お釣り</div>
                        <div class="col-12 modal-ch-change text-primary text-end">{{ this.change.toLocaleString() }} 円</div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default ({
    name: "change",
    props: {
        change: {
            type: Number,
            required: true,
        },

    },
})

</script>
