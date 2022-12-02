<template>
    <div>
        <div class="card col-12 py-2 h4 text-center position-relative" v-if="('name' in customer)">
            {{ customer.name + '　様' }}
            <div class="position-absolute ci-invoice-mark" v-if="Boolean(customer.is_invoice)">請</div>
        </div>
        <div class="card col-12 py-2 h4 text-center" v-else>
            顧客情報が選択されていません
        </div>
        <div class="card col-12 text-center">
            <table class="table h-100 csinfo-table">
                <thead>
                    <tr>
                        <th>CS情報</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Line1</td></tr>
                    <tr><td>Line2</td></tr>
                    <tr><td>Line3</td></tr>
                    <tr><td>Line4</td></tr>
                    <tr><td>Line5</td></tr>
                    <tr><td>Line6</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default ({
    props: {
        customer: {
            required: true
        }
    },
})
</script>
