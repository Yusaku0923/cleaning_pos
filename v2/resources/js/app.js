import './bootstrap';
import * as bootstrap from 'bootstrap';
import { createApp } from 'vue';
import CustomerInfoComponent from './components/CustomerInfoComponent.vue';
import CustomerCreateComponent from './components/CustomerCreateComponent.vue';
import CustomerSearchComponent from './components/CustomerSearchComponent.vue';
import ManagerSelectButtonComponent from './components/ManagerSelectButtonComponent.vue';

// 旧 (Laravel Mix) と同じく Bootstrap 5.2.2 の JS を同梱 (data-bs-* のモーダル等)
window.bootstrap = bootstrap;

/**
 * Vue 部品を旧 resources/js/app.js と同じケバブケース名で登録する。
 * 部品を移植したらここに足す (名前は旧と同じにする)。
 *   'order-component', 'manager-component', 'daily-report-search-component', 'return-component',
 *   'customer-info-component', 'invoice-component', 'payment-component', 'payment-confimation-component',
 *   'customer-create-component', 'customer-search-component', 'history-component', 'all-history-component',
 *   'history-card-component', 'clothes-mst-component', 'manager-select-btn-component',
 *   'customer-display-component', 'delivery-sp-entry-component', 'delivery-note-component'
 */
export const components = {
    'customer-info-component': CustomerInfoComponent,
    'customer-create-component': CustomerCreateComponent,
    'customer-search-component': CustomerSearchComponent,
    'manager-select-btn-component': ManagerSelectButtonComponent,
};

export function registerComponents(app) {
    for (const [name, component] of Object.entries(components)) {
        app.component(name, component);
    }
    return app;
}

// 旧と同じく #app 全体を Vue のテンプレートとして mount する
const el = document.getElementById('app');
if (el) {
    const app = createApp({});
    registerComponents(app);
    app.mount(el);
}
