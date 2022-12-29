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
                <thead class="cs-field">
                    <tr>
                        <th>CS情報</th>
                    </tr>
                </thead>
                <tbody class="py-1" style="height: 14.5vh;">
                    <tr v-for="row in disp" :key="row.id">
                        <td class="d-flex">
                            <div class="col-9">{{ row.information }}</div>
                            <div class="col-3 text-end">{{ dateFormater(row.created_at, 'YYYY/MM/DD') }}</div>
                        </td>
                    </tr>
                    <template v-if="disp.length < 4">
                        <tr v-for="row in (4 - disp.length)" :key="row * -1">
                            <td style="height:3.6vh;">
                                <div class="col-9"></div>
                                <div class="col-3 text-end"></div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <customer-info-edit-modal
            @close="close"
            @add="add"
            @delete="deleteRow"
            :info="base"
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
            base: [],
            disp: [],
        }
    },
    components: {
        'customer-info-edit-modal': CustomerInfosEditModal,
    },
    mounted() {
        this.base = this.info;
        let delimt = 0;
        if (this.base.length > 4) {
            delimt = -4;
        } else {
            delimt = this.base.length * -1;
        }
        this.disp = this.base.slice(delimt);
    },
    methods: {
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
        },
        show: function() {
            if (Object.keys(this.customer).length) {
                this.showModal = true;
            }
        },
        close: function() {
            this.showModal = false;
        },
        add: async function(inputInformation) {
            let self = this;
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            return await axios.post('/api/customer_info/store', {
                information: inputInformation,
            })
            .then(function (response) {
                console.log(response.data);
                self.base = response.data.info;
                self.disp = self.base.slice(-4);
                self.base.splice();
                self.disp.splice();
                return;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        deleteRow: function(id) {
            let self = this;
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            return axios.post('/api/customer_info/delete', {
                id: id,
            })
            .then(function (response) {
                self.base = response.data.info;
                self.disp = self.base.slice(-4);
                self.base.splice();
                self.disp.splice();
                return;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
    },
})
</script>
