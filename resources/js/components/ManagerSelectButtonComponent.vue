<template>
    <div class="col-3 col-3-custom">
        <button type="button" class="card col-12  mr-1 text-center lh-leftbtn cbtn-teal fs-20" @click="show">
            <div class="mx-auto">担当者<br>変更</div>
        </button>
        <manager-select-modal
            @close="close"
            :managers="managers"
            :selected="selected"
            v-if="showModal"
        ></manager-select-modal>
    </div>
</template>

<script>
import ManagerSelectModal from './Modals/ManagerSelectModalComponent';
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
            showModal: false
        }
    },
    components: {
        'manager-select-modal': ManagerSelectModal,
    },
    methods: {
        show: function() {
            this.showModal = true;
        },
        close: function() {
            this.showModal = false;
        },
    },
}
</script>