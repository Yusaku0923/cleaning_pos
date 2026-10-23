import { P } from './printerConstants.js';

/**
 * レシート (お預り票) の印刷命令を組み立てる純粋関数。
 * 旧 ReceiptPrinter.vue の print() と同じ順・同じ値の呼び出し列を返す (send は含まない。EposPrinter.print が最後に送る)。
 * 戻り値: [['addFeed'], ['addTextFont', 'font_b'], ...] — ePOS の Printer に対して printer[method](...args) と呼ぶ。
 *
 * @param {object} receipt  GET /api/receipt/{order} の JSON (旧 fetchReceiptInfo と同じキー)
 * @param {{storeName?: string, address?: string, tel?: string}} settings  店名・住所・電話の上書き。省略時は receipt の値 (旧と同じ)
 * @param {{orderId: number|string, isReissue?: boolean}} options  伝票番号 (旧 printReceipt の order_id) と再発行か
 * @returns {Array<[string, ...any]>}
 */
export function buildReceiptCommands(receipt, settings = {}, { orderId, isReissue = false } = {}) {
    const cmds = [];
    const add = (method, ...args) => cmds.push([method, ...args]);

    const storeName = settings.storeName ?? receipt.store_name;
    const storeAddress = settings.address ?? receipt.store_address;
    const storeTel = settings.tel ?? receipt.store_tel;
    const customerTel = receipt.customer_tel ?? '';
    const { customer_name_kana, customer_name, manager_name, ordered_at, order_list, total_count, amount, discount, payment, tax, paid_at, is_invoice } = receipt;

    // ドロワー開錠: 初回発行・現金・支払い済みのときだけ (請求書払い・再発行・未収では開けない)
    const shouldOpenDrawer = !isReissue && !is_invoice && paid_at !== null;
    if (shouldOpenDrawer) {
        add('addPulse', P.DRAWER_1, P.PULSE_100);
    }

    add('addFeed');
    add('addFeed');
    add('addTextFont', P.FONT_B);
    add('addTextLineSpace', 30);
    add('addTextLang', 'ja');
    add('addTextSmooth', true);
    add('addTextAlign', P.ALIGN_CENTER);
    add('addTextSize', 1, 2);
    add('addText', storeName);
    add('addTextSmooth', false);
    add('addFeed');
    add('addFeed');
    add('addTextSize', 1, 1);
    add('addText', storeAddress + ' (TEL' + storeTel + ')');

    add('addFeed');
    add('addFeed');
    add('addTextStyle', true, false, true, P.COLOR_1);
    add('addTextSize', 2, 2);
    add('addText', 'お預り票');
    add('addFeed');
    add('addFeed');
    add('addTextAlign', P.ALIGN_LEFT);
    add('addTextPosition', 20);
    add('addTextSize', 1, 1);
    add('addTextStyle', false, false, false, P.COLOR_1);
    add('addText', 'お名前');
    add('addFeed');
    add('addTextPosition', 40);
    add('addText', customer_name_kana);
    add('addFeed');
    add('addTextPosition', 40);
    add('addTextSize', 2, 2);
    add('addText', customer_name + ' 様');
    add('addFeed');
    add('addFeed');
    add('addTextSize', 1, 1);
    add('addTextPosition', 20);
    add('addText', 'お電話 ' + customerTel);
    add('addTextPosition', 230);
    add('addText', '担当者:' + manager_name);
    add('addFeed');
    add('addHLine', 0, 575, P.LINE_MEDIUM);
    add('addFeed');

    add('addText', ordered_at);
    add('addTextSize', 2, 2);
    add('addTextPosition', 270);
    add('addText', orderId);
    add('addFeed');
    add('addHLine', 0, 575, P.LINE_THIN);
    add('addFeed');

    for (const line of order_list) {
        // count > 1: まとめ表示 (例 9-580 X 3 / ～9-584)、tag_start ≠ tag_end: 範囲 (スリーピース等)、それ以外: 1 行
        const isMultiple = line.count > 1;
        const isRange = line.tag_start !== line.tag_end;
        add('addTextPosition', 10);
        add('addTextSize', 2, 2);
        add('addText', line.tag_start);
        add('addTextSize', 1, 2);
        add('addTextPosition', 130);
        add('addText', line.name);
        if (isMultiple || isRange) {
            if (isMultiple) {
                add('addTextPosition', 340);
                add('addText', 'X ' + line.count);
            }
            add('addFeed');
            // 2 行目の開始位置を左に戻す (機種によっては LF 後に X 座標が戻らない)
            add('addTextPosition', 10);
            add('addTextSize', 2, 2);
            add('addText', '～' + line.tag_end);
            add('addTextPosition', 350);
            add('addTextSize', 1, 2);
        } else {
            add('addTextPosition', 350);
        }
        add('addText', line.price.toLocaleString());
        add('addFeed');
    }

    add('addFeed');
    add('addHLine', 10, 200, P.LINE_THIN);
    add('addFeed');
    add('addTextPosition', 10);
    add('addTextSize', 1, 1);
    add('addText', '点数　');
    add('addTextPosition', 100);
    add('addText', total_count.toLocaleString());
    add('addTextPosition', 200);
    add('addText', '小計');
    add('addTextPosition', 340);
    // 小計 (旧と同じく amount + discount。0 のときは空)
    const subtotal = amount + discount;
    if (subtotal !== 0) {
        add('addText', subtotal.toLocaleString());
    }
    add('addFeed');
    add('addHLine', 0, 575, P.LINE_THIN);
    add('addFeed');
    add('addTextPosition', 10);
    add('addText', '点数');
    add('addTextPosition', 100);
    add('addText', total_count);
    add('addTextPosition', 200);
    add('addText', '伝票合計');
    add('addTextPosition', 340);
    add('addText', amount.toLocaleString());
    add('addFeed');
    add('addFeed');
    add('addTextPosition', 200);
    add('addText', '(うち消費税');
    add('addTextPosition', 360);
    add('addText', tax + '）');

    add('addFeed');
    add('addHLine', 0, 575, P.LINE_MEDIUM);
    add('addFeed');
    add('addTextSize', 2, 2);
    add('addTextPosition', 50);
    if (is_invoice) {
        add('addTextAlign', P.ALIGN_CENTER);
        add('addTextSize', 1, 2);
        add('addText', '請求書払い');
    } else if (paid_at !== null) {
        add('addText', '合計額');
        add('addTextPosition', 240);
        add('addText', amount.toLocaleString());
        add('addFeed');
        add('addHLine', 0, 575, P.LINE_THIN);
        add('addFeed');
        add('addTextSize', 1, 1);
        add('addTextAlign', P.ALIGN_LEFT);
        add('addTextPosition', 10);
        add('addText', 'お預り');
        add('addTextPosition', 120);
        add('addText', payment.toLocaleString());
        add('addTextPosition', 240);
        add('addText', 'お釣り');
        add('addTextAlign', P.ALIGN_RIGHT);
        add('addTextPosition', 340);
        add('addText', (payment - amount).toLocaleString());
    } else {
        add('addText', '未収額');
        add('addTextPosition', 240);
        add('addText', (amount - payment).toLocaleString());
        add('addFeed');
        add('addHLine', 0, 575, P.LINE_THIN);
    }
    add('addFeed');
    add('addFeed');
    add('addTextSize', 1, 1);
    add('addTextPosition', 0);
    add('addText', 'いつもご利用ありがとうございます。');
    add('addFeed');
    add('addTextPosition', 0);
    add('addText', '今後ともよろしくお願いいたします。');
    add('addFeed');
    add('addHLine', 0, 575, P.LINE_THIN);
    add('addFeed');
    if (paid_at === null) {
        add('addTextAlign', P.ALIGN_CENTER);
        add('addTextSize', 2, 2);
        add('addText', '未　収');
    }
    add('addFeed');
    add('addFeed');
    add('addCut', P.CUT_FEED);

    return cmds;
}
