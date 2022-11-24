<template>
    <div class="col-12">
        <div class="col-10 mx-auto">
            <div class="card col-12 py-3 mb-4 h4 text-center">
                顧　客　検　索
            </div>
            <div class="card col-12 py-4">
                <div class="form-group col-6 mx-auto">
                    <input type="text" class="form-control form-control-lg" placeholder="名前、フリガナ、電話番号"
                        v-model="keyword"
                        @keydown.enter="search($event.keyCode)"/>
                </div>
            </div>
        </div>
        <div class="col-10 mx-auto my-3">
            <div class="card col-12">
                <div class="col-10 d-flex mx-auto mt-3 dr-body-column-inner bg-primary text-white" >
                    <div class="col-7 text-center">お名前</div>
                    <div class="col-3 text-center">最終来店日</div>
                    <div class="col-2 text-center">来店回数</div>
                </div>
                <div class="col-12 overflow-scroll" style="height: 380px;font-size: 24px;">

                    <a class="card col-10 mx-auto my-3 py-3 text-decoration-none text-dark"
                        v-for="customer in customers"
                        :key="customer.id"
                        :href="url(customer.id)">
                        <div class="col-12 d-flex">
                            <div class="col-4 text-end px-1">{{ customer.name }}</div>
                            <div class="col-3 text-start px-1">({{ customer.name_kana }})</div>
                            <div class="col-3 text-center">{{ customer.latest_visit !== null ? dateFormater(customer.latest_visit, 'YYYY/MM/DD'): '-' }}</div>
                            <div class="col-2 text-center">{{ customer.number_of_visits }}回</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import moment from "moment";
export default ({
    props: {
        token: {
            type: String,
            requied: true,
        },
        manager_id: {
            type: Number,
            requied: true,
        },
    },
    data() {
        return {
            keyword: '',
            customers: [],
        }
    },
    methods: {
        search: async function(keyCode) {
            if (keyCode !== 13) return;
            let result = await this.send();
            if (result !== undefined || result.length !== 0) {
                this.customers = result;
                this.customers.splice();
            }
        },
        send: async function() {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
            return await axios.post('/api/customer/search', {
                manager_id: this.manager_id,
                keyword: this.keyword
            })
            .then(function (response) {
                return response.data.customers;
            })
            .catch(function (error) {
                console.log(error);
                return;
            });
        },
        url: function(id) {
            return '/customer/select/' + id
        },
        // Utilityにまとめる
        dateFormater: function(date, format = 'MM/DD') {
            return moment(date).format(format);
        },
    }
})
</script>
