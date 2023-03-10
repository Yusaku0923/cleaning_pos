<template>
    <div>
        <div class="form-group">
            <label for="customer_name" class="h4">お名前</label><span class="required-label">必須</span>
            <input type="text" class="form-control form-control-lg" name="name" id="customer_name" placeholder="お名前" v-model="customer_name">
        </div>
        <div class="form-group mt-2">
            <label for="customer_name_kana" class="h4">お名前(カナ)</label><span class="required-label">必須</span>
            <input type="text" class="form-control form-control-lg" name="name_kana" id="customer_name_kana" placeholder="お名前(カナ)" v-model="customer_name_kana">
        </div>
        <div class="form-group mt-2">
            <label for="customer_phone_number" class="h4">電話番号</label>
            <input type="tel" class="form-control form-control-lg" name="phone_number" id="customer_phone_number" placeholder="電話番号">
        </div>
        <div class="form-group mt-3 px-2 d-flex justify-content-between">
            <div class="col-3">
                <label for="is_invoice" class="form-label fs-20">請求書払い</label>
                <div class="col-12 d-flex fs-20">
                    <div class="col-6">
                        <input type="radio" id="is_invoice_true" class="form-check-input" name="is_invoice" value="1" v-model="is_invoice" />
                        <label for="is_invoice_true">する</label>
                    </div>
                    <div class="col-6">
                        <input type="radio" id="is_invoice_false" class="form-check-input" name="is_invoice" value="0" v-model="is_invoice" />
                        <label for="is_invoice_false">しない</label>
                    </div>
                </div>
            </div>
            <div class="col-3 px-2 border-top-0 border-bottom-0 border-end-0 border border-2" v-show="is_invoice === '1'" style="border-color: #f8fafc;">
                <label for="check_payment" class="form-label fs-20">入金確認</label>
                <div class="col-12 d-flex fs-20">
                    <div class="col-6">
                        <input type="radio" id="check_payment_true" class="form-check-input" name="check_payment" value="1" checked />
                        <label for="check_payment_true">する</label>
                    </div>
                    <div class="col-6">
                        <input type="radio" id="check_payment_false" class="form-check-input" name="check_payment" value="0" />
                        <label for="check_payment_false">しない</label>
                    </div>
                </div>
            </div>
            <div class="col-3 px-2 border-top-0 border-bottom-0 border-end-0 border  border-2" v-show="is_invoice === '1'" style="border-color: #f8fafc;">
                <label for="check_return" class="form-label fs-20">返却確認</label>
                <div class="col-12 d-flex fs-20">
                    <div class="col-6">
                        <input type="radio" id="check_retrun_true" class="form-check-input" name="check_return" value="1" checked />
                        <label for="check_retrun_true">する</label>
                    </div>
                    <div class="col-6">
                        <input type="radio" id="check_return_false" class="form-check-input" name="check_return" value="0" />
                        <label for="check_return_false">しない</label>
                    </div>
                </div>
            </div>
            <div class="col-3 px-2 border-top-0 border-bottom-0 border-end-0 border  border-2" v-show="is_invoice === '1'" style="border-color: #f8fafc;">
                <label for="cutoff_date" class="form-label fs-20">締め日</label>
                <select id="cutoff_date" name="cutoff_date" class="form-select" aria-label="締め日">
                    <option
                        v-for="(date, key, _) in cutoff"
                        :key="key"
                        :value="key"
                        :disabled="key === 0"
                    >{{ date }}{{key !== 0 ? '日': ''}}</option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
import * as AutoKana from 'vanilla-autokana';

export default ({
    props: {
        cutoff: {
            required: true
        },
    },
    data() {
        return {
            customer_name: '',
            customer_name_kana: '',
            is_invoice: "0",
            autokana: {}
        }
    },
    mounted() {
        this.autokana = AutoKana.bind('#customer_name', '#customer_name_kana', { katakana: true });
    },
    methods: {
        handleNameInput: function() {
            let kana = this.autokana.getFurigana();
            if (kana.length != 0) {
                this.customer_name_kana = kana;
            }
        }
    }
});
</script>

