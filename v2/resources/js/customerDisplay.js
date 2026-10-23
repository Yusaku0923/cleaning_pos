/**
 * 顧客表示 (カスタマーディスプレイ) へ送る。旧は POST /api/broadcast と、注文画面を開いた GET (OrdersController::create)。
 * v2 は GET では送らない。送り先 POST /api/customer-display は Task 7 で作る。
 * TODO(Task 7): ここで axios.post('/api/customer-display', payload) を呼ぶ。それまでは何もしない。
 */
export function pushCustomerDisplay(payload) {
    return Promise.resolve(payload);
}
