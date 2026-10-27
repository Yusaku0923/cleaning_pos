import './bootstrap';
import * as bootstrap from 'bootstrap';
import { createApp } from 'vue';
import AllHistoryComponent from './components/AllHistoryComponent.vue';
import CustomerInfoComponent from './components/CustomerInfoComponent.vue';
import CustomerCreateComponent from './components/CustomerCreateComponent.vue';
import CustomerDisplayComponent from './components/CustomerDisplayComponent.vue';
import CustomerSearchComponent from './components/CustomerSearchComponent.vue';
import DailyReportSearchComponent from './components/DailyReportSearchComponent.vue';
import HistoryCardComponent from './components/HistoryCardComponent.vue';
import HistoryComponent from './components/HistoryComponent.vue';
import InvoiceComponent from './components/InvoiceComponent.vue';
import ManagerSelectButtonComponent from './components/ManagerSelectButtonComponent.vue';
import OrderComponent from './components/OrderComponent.vue';
import PaymentComponent from './components/PaymentComponent.vue';
import PaymentConfirmationComponent from './components/PaymentConfirmationComponent.vue';
import ReturnComponent from './components/ReturnComponent.vue';
import ClothesMasterComponent from './components/ClothesMasterComponent.vue';
import DeliveryNoteComponent from './components/DeliveryNoteComponent.vue';
import DeliverySpEntryComponent from './components/DeliverySpEntryComponent.vue';
import errorLogger from './utils/ErrorLogger';
import { pushFlashedCustomerDisplay } from './customerDisplay.js';

// クライアントエラー収集 (旧と同じ。window.onerror / unhandledrejection / console.error・warn を /api/client-error へ送る)
if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    errorLogger.init();
    window.errorLogger = errorLogger;
}

// 旧 (Laravel Mix) と同じく Bootstrap 5.2.2 の JS を同梱 (data-bs-* のモーダル等)
window.bootstrap = bootstrap;

/**
 * Vue 部品を旧 resources/js/app.js と同じケバブケース名で登録する。
 * 部品を移植したらここに足す (名前は旧と同じにする。綴りの誤りだけ直す: payment-confimation → payment-confirmation)。
 *   'order-component', 'manager-component', 'daily-report-search-component', 'return-component',
 *   'customer-info-component', 'invoice-component', 'payment-component', 'payment-confimation-component',
 *   'customer-create-component', 'customer-search-component', 'history-component', 'all-history-component',
 *   'history-card-component', 'clothes-mst-component', 'manager-select-btn-component',
 *   'customer-display-component', 'delivery-sp-entry-component', 'delivery-note-component'
 */
export const components = {
    'customer-info-component': CustomerInfoComponent,
    'customer-create-component': CustomerCreateComponent,
    'customer-display-component': CustomerDisplayComponent,
    'customer-search-component': CustomerSearchComponent,
    'manager-select-btn-component': ManagerSelectButtonComponent,
    'order-component': OrderComponent,
    'return-component': ReturnComponent,
    'payment-component': PaymentComponent,
    'history-component': HistoryComponent,
    'all-history-component': AllHistoryComponent,
    'history-card-component': HistoryCardComponent,
    'invoice-component': InvoiceComponent,
    'payment-confirmation-component': PaymentConfirmationComponent,
    'daily-report-search-component': DailyReportSearchComponent,
    'clothes-mst-component': ClothesMasterComponent,
    'delivery-note-component': DeliveryNoteComponent,
    'delivery-sp-entry-component': DeliverySpEntryComponent,
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
    // 旧 (Vue 2 の実行時コンパイラ) は要素の間の空白・改行を残した。Vue 3 の既定 'condense' は改行を含む空白を消すので、
    // Blade で改行して並べたボタンの間の隙間 (inline-block の空白) がなくなる (納品書管理の「商品管理」など)。旧と同じにする
    app.config.compilerOptions.whitespace = 'preserve';
    registerComponents(app);
    app.mount(el);
}

// 顧客選択 (GET) のあと 1 回だけ、顧客表示へ「いらっしゃいませ」を送る (旧は GET の中で送っていた)
if (typeof document !== 'undefined') {
    pushFlashedCustomerDisplay();
}
