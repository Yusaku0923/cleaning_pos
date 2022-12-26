<template>
    <div>
        <div class="card card-border col-12 py-2 h4 text-center position-relative" v-if="('name' in customer)">
            {{ customer.name + '　様' }}
            <div class="position-absolute ci-invoice-mark" v-if="Boolean(customer.is_invoice)">請</div>
        </div>
        <div class="card card-border col-12 py-2 h4 text-center" v-else>
            顧客情報が選択されていません
        </div>
        <div class="card card-border col-12 text-center" @click="show">
            <table class="table h-100 csinfo-table">
                <thead>
                    <tr>
                        <th>CS情報</th>
                    </tr>
                </thead>
                <tbody style="height: 14.5vh;">
                    <tr v-for="row in disp" :key="row.id">
                        <td class="d-flex">
                            <div class="col-9">{{ row.information }}</div>
                            <div class="col-3 text-end">{{ dateFormater(row.created_at, 'YYYY/MM/DD') }}</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <customer-info-edit-modal
            @close="close"
            :info="info"
            :token="token"
            v-if="showModal"
        ></customer-info-edit-modal>
    </div>
</template>

<script>
import moment from "moment";
import CustomerInfosEditModal from './Modals/CustomerInfoEditModalComponent';

export default ({
    props: {
        customer: {
            required: true
        },
        token: {
            required: true
        },
        info: {
            required: false
        }
    },
    data() {
        return {
            showModal: false,
            disp: this.info.splice(-5),
        }
    },
    components: {
        'customer-info-edit-modal': CustomerInfosEditModal,
    },
    methods: {
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
        },
        show: function() {
            this.showModal = true;
        },
        close: function() {
            this.showModal = false;
        }
    },
})
</script>
