<template>
    <div class="mx-auto form-group d-flex justify-content-between dr-search">
        <label class="dr-search-label">
            <input type="date" class="dr-search-form" v-model="inputDate">
        </label>
        <a :href="searchUrl">
            <button class="cbtn dr-search-btn ml-2"><i class="fa-solid fa-magnifying-glass"></i></button>
        </a>
    </div>
</template>

<script>

export default {
    props: {
        date: {
            required: true
        }
    },
    data() {
        return {
            inputDate: '',
        }
    },
    computed: {
        // 担当者 (?m=) を引き継ぐ (旧はセッション)
        searchUrl() {
            const params = new URLSearchParams(window.location.search);
            params.set('date', this.inputDate);
            return '/daily_report?' + params.toString();
        },
    },
    methods: {
    }
}
</script>
