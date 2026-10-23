// 旧 resources/js/components/Functions/ReceiptPrinter.vue (git 087424e) の印刷命令の組み立て部分を、
// 一字一句そのまま写したもの (receiptCommands.test.js で新 buildReceiptCommands と突き合わせる)。
//   49〜71 行目: レシートデータから変数を取り出す部分 (printReceipt の中)
//   176〜476 行目: function print() { ... } 全体
// 写した行は変更しない。外側の関数と、旧のクロージャにあった printer / disconnectDevice を引数で受け取る部分、
// 最後の print() 呼び出しだけを足している。旧では order_id・isReissue は printReceipt の引数。
// このファイルは旧との比較専用で、アプリからは読まない。

export function legacyReceiptCommands(receipt, order_id, isReissue, printer, disconnectDevice) {
            let store_name = receipt["store_name"];
            let store_address = receipt["store_address"];
            let store_tel = receipt["store_tel"];
            let customer_name_kana = receipt["customer_name_kana"];
            let customer_name = receipt["customer_name"];
            let customer_tel = receipt["customer_tel"] ?? "";
            let manager_name = receipt["manager_name"];
            let ordered_at = receipt["ordered_at"];
            let order_list = receipt["order_list"];
            let total_count = receipt["total_count"];
            let amount = receipt["amount"];
            let discount = receipt["discount"];
            let payment = receipt["payment"];
            let tax = receipt["tax"];
            let paid_at = receipt["paid_at"];
            let is_invoice = receipt["is_invoice"];
            let ip_address = receipt["ip_address"];
            
            // ドロワー開錠の条件判定
            // Orderパネルで現金での注文をしたときのみ開錠
            // 条件：初回発行（!isReissue）かつ現金支払い（!is_invoice）かつ支払い済み（paid_at !== null）
            // 領収書払い（is_invoice = true）やレシート再発行（isReissue = true）では開かない
            const shouldOpenDrawer = !isReissue && !is_invoice && paid_at !== null;

            function print() {
                console.log("【印刷処理開始】");
                
                // ドロワー開錠（Orderパネルで現金での注文をしたときのみ）
                if (shouldOpenDrawer) {
                    console.log("【ドロワー開錠】現金での注文（初回発行）のため、ドロワーを開きます");
                    printer.addPulse(
                        printer.DRAWER_1, // DKポート1（通常これ）
                        printer.PULSE_100 // パルス幅（標準）
                    );
                } else {
                    console.log("【ドロワー開錠スキップ】", 
                        isReissue ? "レシート再発行のため" : 
                        is_invoice ? "領収書払いのため" : 
                        paid_at === null ? "未収のため" : 
                        "条件不一致");
                }

                printer.addFeed();
                printer.addFeed();
                printer.addTextFont(printer.FONT_B);
                printer.addTextLineSpace(30);
                printer.addTextLang("ja");
                printer.addTextSmooth(true);
                printer.addTextAlign(printer.ALIGN_CENTER);
                printer.addTextSize(1, 2);

                // store name
                printer.addText(store_name);

                printer.addTextSmooth(false);
                printer.addFeed();
                printer.addFeed();
                printer.addTextSize(1, 1);

                // store address, store tel
                printer.addText(store_address + " (TEL" + store_tel + ")");

                printer.addFeed();
                printer.addFeed();
                printer.addTextStyle(true, false, true, printer.COLOR_1);
                printer.addTextSize(2, 2);
                printer.addText("お預り票");
                printer.addFeed();
                printer.addFeed();
                printer.addTextAlign(printer.ALIGN_LEFT);
                printer.addTextPosition(20);
                printer.addTextSize(1, 1);
                printer.addTextStyle(false, false, false, printer.COLOR_1);
                printer.addText("お名前");
                printer.addFeed();
                printer.addTextPosition(40);

                // customer name kana
                printer.addText(customer_name_kana);

                printer.addFeed();
                printer.addTextPosition(40);
                printer.addTextSize(2, 2);

                // customer name
                printer.addText(customer_name + " 様");

                printer.addFeed();
                printer.addFeed();
                printer.addTextSize(1, 1);
                printer.addTextPosition(20);

                // customer tel
                printer.addText("お電話 " + customer_tel);

                printer.addTextPosition(230);

                // manager_name
                printer.addText("担当者:" + manager_name);

                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_MEDIUM);
                printer.addFeed();

                // ordered at
                printer.addText(ordered_at);
                printer.addTextSize(2, 2);
                printer.addTextPosition(270);

                // identity
                printer.addText(order_id);

                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();

                // order list
                for (const order of order_list) {
                    // countが1より大きい場合は統合表示（X count形式）
                    // tag_startとtag_endが異なる場合は範囲表記（スリーピースなど）
                    const isMultiple = order["count"] > 1;
                    const isRange = order["tag_start"] !== order["tag_end"];
                    
                    console.log(`【印刷処理】${order["name"]} - count: ${order["count"]}, tag_start: ${order["tag_start"]}, tag_end: ${order["tag_end"]}, isMultiple: ${isMultiple}, isRange: ${isRange}`);
                    
                    if (isMultiple) {
                        // 統合表示（例：9-580 X 3 ～9-584）
                        printer.addTextPosition(10);
                        printer.addTextSize(2, 2);

                        // tag start
                        printer.addText(order["tag_start"]);

                        printer.addTextSize(1, 2);
                        printer.addTextPosition(130);

                        // clothes name
                        printer.addText(order["name"]);
                        
                        printer.addTextPosition(340);

                        // count
                        printer.addText("X " + order["count"]);

                        printer.addFeed();
                        // 次行の開始位置を左に戻す（機種によってはLF後にX座標が戻らず、2行目が用紙外に出るため）
                        printer.addTextPosition(10);
                        printer.addTextSize(2, 2);

                        // tag end
                        printer.addText("～" + order["tag_end"]);

                        printer.addTextPosition(350);
                        printer.addTextSize(1, 2);

                        // clothes price (合計金額)
                        printer.addText(order["price"].toLocaleString());

                        printer.addFeed();
                    } else if (isRange) {
                        // 範囲表記（スリーピースなど、例：9-587～9-589）
                        printer.addTextPosition(10);
                        printer.addTextSize(2, 2);

                        // tag start
                        printer.addText(order["tag_start"]);

                        printer.addTextSize(1, 2);
                        printer.addTextPosition(130);

                        // clothes name
                        printer.addText(order["name"]);

                        printer.addFeed();
                        // 次行の開始位置を左に戻す（機種によってはLF後にX座標が戻らず、2行目が用紙外に出るため）
                        printer.addTextPosition(10);
                        printer.addTextSize(2, 2);

                        // tag end
                        printer.addText("～" + order["tag_end"]);

                        printer.addTextPosition(350);
                        printer.addTextSize(1, 2);

                        // clothes price
                        printer.addText(order["price"].toLocaleString());

                        printer.addFeed();
                    } else {
                        // 単一タグまたは複数タグ発行商品の個別表示
                        printer.addTextPosition(10);
                        printer.addTextSize(2, 2);

                        // tag
                        printer.addText(order["tag_start"]);

                        printer.addTextSize(1, 2);
                        printer.addTextPosition(130);

                        // clothes name
                        printer.addText(order["name"]);

                        printer.addTextPosition(350);

                        // clothes price
                        printer.addText(order["price"].toLocaleString());

                        printer.addFeed();
                    }
                }

                printer.addFeed();
                printer.addHLine(10, 200, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextPosition(10);
                printer.addTextSize(1, 1);
                printer.addText("点数　");
                printer.addTextPosition(100);

                // total count
                printer.addText(total_count.toLocaleString());

                printer.addTextPosition(200);
                printer.addText("小計");
                printer.addTextPosition(340);

                // subtotal (小計が0の場合は空にする)
                const subtotal = amount + discount;
                if (subtotal !== 0) {
                    printer.addText(subtotal.toLocaleString());
                }
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                printer.addTextPosition(10);
                printer.addText("点数");
                printer.addTextPosition(100);

                // total count
                printer.addText(total_count);

                printer.addTextPosition(200);
                printer.addText("伝票合計");
                printer.addTextPosition(340);

                // total
                printer.addText(amount.toLocaleString());
                printer.addFeed();
                printer.addFeed();
                printer.addTextPosition(200);
                printer.addText("(うち消費税");
                printer.addTextPosition(360);

                // tax
                printer.addText(tax + "）");

                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_MEDIUM);
                printer.addFeed();
                printer.addTextSize(2, 2);
                printer.addTextPosition(50);
                if (is_invoice) {
                    printer.addTextAlign(printer.ALIGN_CENTER);
                    printer.addTextSize(1, 2);
                    printer.addText("請求書払い");
                } else if (paid_at !== null) {
                    printer.addText("合計額");
                    printer.addTextPosition(240);

                    // total
                    printer.addText(amount.toLocaleString());
                    printer.addFeed();
                    printer.addHLine(0, 575, printer.LINE_THIN);
                    printer.addFeed();
                    printer.addTextSize(1, 1);
                    printer.addTextAlign(printer.ALIGN_LEFT);
                    printer.addTextPosition(10);
                    printer.addText("お預り");
                    printer.addTextPosition(120);
                    printer.addText(payment.toLocaleString());
                    printer.addTextPosition(240);
                    printer.addText("お釣り");
                    printer.addTextAlign(printer.ALIGN_RIGHT);
                    printer.addTextPosition(340);
                    printer.addText((payment - amount).toLocaleString());
                } else {
                    printer.addText("未収額");
                    printer.addTextPosition(240);
                    printer.addText((amount - payment).toLocaleString());
                    printer.addFeed();
                    printer.addHLine(0, 575, printer.LINE_THIN);
                }
                printer.addFeed();
                printer.addFeed();
                printer.addTextSize(1, 1);
                printer.addTextPosition(0);
                printer.addText("いつもご利用ありがとうございます。");
                printer.addFeed();
                printer.addTextPosition(0);
                printer.addText("今後ともよろしくお願いいたします。");
                printer.addFeed();
                printer.addHLine(0, 575, printer.LINE_THIN);
                printer.addFeed();
                if (paid_at === null) {
                    printer.addTextAlign(printer.ALIGN_CENTER);
                    printer.addTextSize(2, 2);
                    printer.addText("未　収");
                }
                printer.addFeed();
                printer.addFeed();
                printer.addCut(printer.CUT_FEED);
                
                console.log("【印刷コマンド送信実行中...】");
                console.log("明細行数:", order_list.length);
                console.log("合計金額:", amount);
                
                try {
                    printer.send();
                    console.log("【印刷コマンド送信完了】プリンターからの応答を待機中...");
                } catch (error) {
                    console.error("【エラー】印刷コマンド送信中にエラーが発生しました:", error);
                    // エラー時も切断
                    disconnectDevice();
                }
            }

            print();
}
