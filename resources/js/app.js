/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue').default;

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('order-component', require('./components/OrderComponent.vue').default);
Vue.component('manager-component', require('./components/ManagerComponent.vue').default);
Vue.component('daily-report-search-component', require('./components/DailyReportSearchComponent.vue').default);
Vue.component('return-component', require('./components/ReturnComponent.vue').default);
Vue.component('customer-info-component', require('./components/CustomerInfoComponent.vue').default);
Vue.component('invoice-component', require('./components/InvoiceComponent.vue').default);
Vue.component('payment-component', require('./components/PaymentComponent.vue').default);
Vue.component('payment-confimation-component', require('./components/PaymentConfimationComponent.vue').default);
Vue.component('customer-create-component', require('./components/CustomerCreateComponent.vue').default);
Vue.component('customer-search-component', require('./components/CustomerSearchComponent.vue').default);
Vue.component('history-component', require('./components/HistoryComponent.vue').default);
Vue.component('all-history-component', require('./components/AllHistoryComponent.vue').default);
Vue.component('history-card-component', require('./components/HistoryCardComponent.vue').default);
Vue.component('clothes-mst-component', require('./components/ClothesMasterComponent.vue').default);
Vue.component('manager-select-btn-component', require('./components/ManagerSelectButtonComponent.vue').default);
Vue.component('customer-display-component', require('./components/CustomerDisplayComponent.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});
