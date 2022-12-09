<template>
    <div class="col-12">
        <div class="col-12 d-flex border-bottom border-2 mt-2 pb-2 dr-header">
            <div class="col-4 iv-title text-center fw-bold mx-auto"
            :class="[ theme !== '' ? theme: '' ]">請求書発行</div>
        </div>
        <div class="col-12 d-flex">
            <div class="col-8 ps-3 iv-left">
                <div class="col-12 iv-left-search">
                    <div class="card card-border mt-2">
                        <div class="card-body col-12 p-1 iv-left-search-field d-flex position-relative">
                            <div class="col-2 iv-left-search-field-column">
                                <div class="col-10 bg-secondary text-white rounded text-center">表示項目</div>
                            </div>
                            <div class="col-2 iv-left-search-field-column">締日：{{ cutoffDate == 0 ? '-': cutoffDate == 99 ? '末日': cutoffDate + '日' }}</div>
                            <div class="col-3 iv-left-search-field-column">対象月：{{ targetMonth === '' ? '-': targetMonth }}</div>
                            <div class="col-4 iv-left-search-field-column">名前：{{ customerName === '' ? '-': customerName }}</div>
                            <div class="position-absolute iv-left-search-field-btn">
                                <button class="modal-cs-btn modal-cs-btn-send" @click="dispSearch = true">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 iv-left-result mt-2">
                    <div class="card card-border iv-left-result-field">
                        <div class="col-11 d-flex mx-auto mt-2 dr-body-column-inner bg-primary text-white">
                            <div class="col-3 text-center">請求対象期間</div>
                            <div class="col-5 text-center">お名前</div>
                            <div class="col-4 text-center">合計（繰越金）</div>
                        </div>
                        <div class="col-12 overflow-scroll iv-left-result-field-list">
                            <div class="col-11 card mx-auto mt-2 py-2 iv-left-result-field-list-card"
                                v-for="invoice in invoicesList"
                                :key="invoice.id"
                                :class="{ 'iv-selected': indexes.includes(invoice.id), 'iv-unselected': !indexes.includes(invoice.id) }"
                                @click="switchSelection(invoice)">
                                <div class="col-12 d-flex">
                                    <div class="col-3 text-center">
                                        {{ dateFormater(invoice.period_start) }} ～ {{ dateFormater(invoice.period_end) }}
                                    </div>
                                    <div class="col-5 text-center">
                                        {{ invoice['name'] }}
                                    </div>
                                    <div class="col-4 text-center">
                                        {{ invoice.amount.toLocaleString() }}円（{{ invoice.carried_over_amount.toLocaleString() }}円）
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-4 px-3 iv-right position-relative">
                <div class="col-12 iv-right-select">
                    <div class="card card-border col-12 mx-auto mt-2 p-1 text-center iv-right-select-label">
                        選択中
                    </div>
                    <div class="card card-border col-12 overflow-scroll mx-auto mt-2 p-2 iv-right-select-field">
                        <div class="card col-12 my-1 py-2 iv-right-select-field-card iv-selected-right"
                            v-for="index in indexes"
                            :key="index"
                            @click="switchSelection(selectedInvoices[index])">
                            <div class="col-12 d-flex">
                                <div class="col-5 text-center">
                                    {{ dateFormater(selectedInvoices[index].period_start) }} ～ {{ dateFormater(selectedInvoices[index].period_end) }}
                                </div>
                                <div class="col-7 text-center">
                                    {{ selectedInvoices[index].name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <a class="iv-reset bg-secondary">
                全選択
            </a>
            <a class="iv-pdf"
                :href="'/invoice/generate?ids=' + params"
                target="_blank" rel="noopener noreferrer">
                印刷
            </a>
        </div>
        <search-modal
            @close="close"
            @search="displaySearchResult"
            :cutoff_date="cutoffDate"
            :target_month="dateFormater(targetMonth, 'YYYY-MM')"
            :customer_name="customerName"
            v-if="dispSearch"
        ></search-modal>
    </div>
</template>

<script>
import moment from "moment";
import SearchModal from "./Modals/InvoiceSearchModalComponent";

export default ({
    props: {
        invoices: {
            type: Array,
            required: true
        },
        manager_id: {
            required: true
        },
        token: {
            Type: String,
            required: true,
        },
        theme: {
            Typr: String,
            required: true
        }
    },
    data() {
        return {
            dispSearch: false,
            cutoffDate: 0,
            targetMonth: this.dateFormater(new Date(), 'YYYY/MM'),
            customerName: '',
            invoicesList: this.invoices,
            selectedInvoices: {},
            indexes: [],
            params: '',
        }
    },
    components: {
        'search-modal': SearchModal,
    },
    methods: {
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
        },
        themeClass: function() {
            return this.theme;
        },
        close: function() {
            this.dispSearch = false;
        },
        switchSelection: function(invoice) {
            if (this.indexes.includes(invoice.id)) {
                this.$delete(this.selectedInvoices, invoice.id);
                this.indexes.splice(this.indexes.indexOf(invoice.id), 1);
            } else {
                this.$set(this.selectedInvoices, invoice.id, invoice);
                this.indexes.push(invoice.id);
            }
            this.params = this.indexes.join(',');
        },
        displaySearchResult: async function(conditions) {
            if (conditions.target_month.length !== 0) {
                conditions.target_month = this.dateFormater(conditions.target_month, 'YYYY-MM');
            }
            let result = await this.search(conditions);
            this.invoicesList = result.invoices;
            this.cutoffDate = conditions.cutoff_date;
            this.targetMonth = conditions.target_month;
            this.customerName = conditions.customer_name;
            this.close();
        },
        search: async function(conditions) {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            return await axios.post('/api/invoice/search', {
                manager_id: this.manager_id,
                conditions: conditions,
            })
            .then(function (response) {
                return response.data;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
    },
})
</script>
