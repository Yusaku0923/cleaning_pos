<template>
    <div>
        <div class="form-group">
            <label for="customer_name" class="h4">お名前</label>
            <input type="text" class="form-control form-control-lg" name="name" id="customer_name" placeholder="お名前" v-model="customer_name">
        </div>
        <div class="form-group mt-2">
            <label for="customer_name_kana" class="h4">お名前(カナ)</label>
            <input type="text" class="form-control form-control-lg" name="name_kana" id="customer_name_kana" placeholder="お名前(カナ)" v-model="customer_name_kana">
        </div>
        <div class="form-group mt-2">
            <label for="customer_phone_number" class="h4">電話番号</label>
            <input type="tel" class="form-control form-control-lg" name="phone_number" id="customer_phone_number" placeholder="電話番号">
        </div>
        <div class="form-group mt-2 d-flex justify-content-between">
            <div class="col-9">
                <label for="customer_birth_day" class="h4">お誕生日</label>
                <input type="date" class="form-control form-control-lg" name="birth_day" id="customer_birth_day" placeholder="お誕生日">
            </div>
            <div class="col-2">
                <label for="customer_sex" class="h4">性別</label>
                <select class="form-control form-control-lg" name="sex" id="customer_sex" placeholder="お誕生日">
                    <option value="">-</option>
                    <option value="0">男</option>
                    <option value="1">女</option>
                    <option value="2">その他</option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
import * as AutoKana from 'vanilla-autokana';

export default ({
    data() {
        return {
            customer_name: '',
            customer_name_kana: '',
        }
    },
    mounted() {
        autokana = AutoKana.bind('#customer_name', '#customer_name_kana', { katakana: true });
    },
    methods: {
        handleNameInput: function() {
            console.log(autokana.getFurigana());
            let kana = autokana.getFurigana();
            if (kana.length != 0) {
                this.customer_name_kana = kana;
            }
        }
    }
});
</script>

