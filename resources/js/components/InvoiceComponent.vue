<template>
    <div class="col-12">
        <div class="col-12 d-flex border-bottom border-2 mt-2 pb-2 dr-header">
            <div class="col-4 iv-title text-center fw-bold mx-auto">請求書発行</div>
        </div>
        <div class="col-12 d-flex">
            <div class="col-8 px-1 iv-left">
                <div class="col-12 iv-left-search">
                    <div class="card mt-2">
                        <div class="card-body col-12 p-1 iv-left-search-field d-flex">
                            <div class="col-2">
                                <div class="col-10 bg-secondary text-white rounded text-center">表示項目</div>
                            </div>
                            <div class="col-2">締日：{{ cutoffDate === 0 ? '-': cutoffDate === 99 ? '末日': cutoffDate + '日' }}</div>
                            <div class="col-3">対象月：{{ targetMonth === '' ? '-': targetMonth }}</div>
                            <div class="col-5">名前：{{ customerName === '' ? '-': customerName }}</div>
                        </div>
                        <div class="card-footer p-1 px-3">
                            <p class="col-12 text-end mb-0">検索項目入力　<i class="fa-solid fa-chevron-down"></i></p>
                        </div>
                    </div>
                </div>
                <div class="col-12 iv-left-result mt-2">
                    <div class="card iv-left-result-field">
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

            <div class="col-4 px-3 iv-right">
                <div class="col-12 iv-right-select">
                    <div class="card col-12 mx-auto mt-2 p-1 text-center iv-right-select-label">
                        選択中
                    </div>
                    <div class="card col-12 overflow-scroll mx-auto mt-2 p-2 iv-right-select-field">
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
        </div>
        <a class="iv-reset">
            全選択
        </a>
        <a class="iv-pdf"
            :href="'/invoice/generate?ids=' + params"
            target="_blank" rel="noopener noreferrer">
            PDF出力
        </a>
    </div>
</template>

<script>
import moment from "moment";

export default ({
    props: {
        invoices: {
            type: Array,
            required: true
        },
    },
    data() {
        return {
            cutoffDate: 0,
            targetMonth: this.dateFormater(new Date(), 'YYYY/MM'),
            customerName: '',
            invoicesList: this.invoices,
            selectedInvoices: {},
            indexes: [],
            params: '',
        }
    },
    methods: {
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
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
        }
    },
})
</script>
