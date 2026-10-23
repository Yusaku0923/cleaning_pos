/**
 * 顧客表示 (カスタマーディスプレイ) へ送る。旧は POST /api/broadcast と、注文画面を開いた GET (OrdersController::create)。
 * v2 は GET では送らず、画面からここ (POST /api/customer-display) で送る。ペイロードは旧と同じ。
 * 失敗しても POS の操作は止めない (旧と同じくログだけ)。
 */
export function pushCustomerDisplay(payload) {
    return window.axios.post('/api/customer-display', payload)
        .catch(function (error) {
            console.log(error);
        });
}

/**
 * 顧客選択 (GET /customer/select) の代わり: サーバーがフラッシュで渡した内容を 1 回だけ送る。
 */
export function pushFlashedCustomerDisplay(doc = document) {
    const meta = doc.querySelector('meta[name="customer-display-push"]');
    if (!meta) return null;
    let payload;
    try {
        payload = JSON.parse(meta.getAttribute('content'));
    } catch (e) {
        return null;
    }
    return pushCustomerDisplay(payload);
}
