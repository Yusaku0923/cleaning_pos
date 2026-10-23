<template></template>

<script>
// 旧 Functions/ReceiptPrinter.vue の薄いラッパー。
// 命令の組み立ては printer/receiptCommands.js (純粋関数)、接続・送信は printer/EposPrinter.js。
import { buildReceiptCommands } from '../../printer/receiptCommands.js';
import { EposPrinter } from '../../printer/EposPrinter.js';

export default {
    created() {
        // 接続中のデバイスを保持する (disconnect 用)。リアクティブにしない
        this.eposPrinter = new EposPrinter();
    },
    methods: {
        fetchReceipt: async function (order_id) {
            return await axios
                .get("/api/receipt/" + order_id)
                .then(function (response) {
                    return response.data;
                })
                .catch(function (error) {
                    console.log(error);
                    return;
                });
        },

        printReceipt: async function (order_id, isReissue = false) {
            console.log("========================================");
            console.log("【レシート印刷開始】");
            console.log("========================================");
            console.log("注文ID:", order_id);
            console.log("再発行:", isReissue ? "はい" : "いいえ");
            console.log("レシートデータ取得中...");

            let receipt = await this.fetchReceipt(order_id);

            if (!receipt) {
                console.error("【エラー】レシートデータの取得に失敗しました");
                return;
            }

            console.log("レシートデータ取得成功");
            console.log("プリンターIPアドレス:", receipt["ip_address"]);
            console.log("明細数:", receipt["order_list"].length);
            console.log("合計金額:", receipt["amount"]);

            // デバッグ: レシート内容をコンソールに表示
            this.debugReceipt(receipt, order_id);

            const commands = buildReceiptCommands(receipt, {}, { orderId: order_id, isReissue });
            try {
                await this.eposPrinter.connect(receipt["ip_address"], 8008);
                await this.eposPrinter.print(commands);
            } catch (e) {
                // 接続できない・送れないときもエラーを出すだけで画面は止めない (旧と同じ)
                console.warn("【印刷できませんでした】", e);
            }
        },

        debugReceipt: function (receipt, order_id) {
            console.log("========================================");
            console.log("【レシートデバッグ情報】");
            console.log("========================================");
            console.log("店舗名:", receipt["store_name"]);
            console.log("住所:", receipt["store_address"]);
            console.log("電話番号:", receipt["store_tel"]);
            console.log("----------------------------------------");
            console.log("お預り票");
            console.log("----------------------------------------");
            console.log("お名前（カナ）:", receipt["customer_name_kana"]);
            console.log("お名前:", receipt["customer_name"] + " 様");
            console.log("お電話:", receipt["customer_tel"] || "");
            console.log("担当者:", receipt["manager_name"]);
            console.log("----------------------------------------");
            console.log("受付日時:", receipt["ordered_at"]);
            console.log("伝票番号:", order_id);
            console.log("----------------------------------------");
            console.log("【明細一覧】");
            console.log("----------------------------------------");
            receipt["order_list"].forEach((order, index) => {
                const isMultiple = order["count"] > 1;
                const isRange = order["tag_start"] !== order["tag_end"];
                
                if (isMultiple) {
                    // 統合表示（例：9-580 X 3 ～9-584）
                    console.log(
                        `${index + 1}. ${order["tag_start"]} | ${
                            order["name"]
                        } | X ${order["count"]}`
                    );
                    console.log(
                        `   ～${order["tag_end"]} | ${order[
                            "price"
                        ].toLocaleString()}円`
                    );
                } else if (isRange) {
                    // 範囲表記（スリーピースなど、例：9-587～9-589）
                    console.log(
                        `${index + 1}. ${order["tag_start"]} | ${
                            order["name"]
                        }`
                    );
                    console.log(
                        `   ～${order["tag_end"]} | ${order[
                            "price"
                        ].toLocaleString()}円`
                    );
                } else {
                    // 単一タグ
                    console.log(
                        `${index + 1}. ${order["tag_start"]} | ${
                            order["name"]
                        } | ${order["price"].toLocaleString()}円`
                    );
                }
            });
            console.log("----------------------------------------");
            console.log("点数:", receipt["total_count"].toLocaleString());
            const subtotal = receipt["amount"] + receipt["discount"];
            if (subtotal !== 0) {
                console.log("小計:", subtotal.toLocaleString() + "円");
            } else {
                console.log("小計: (空)");
            }
            console.log("----------------------------------------");
            console.log("点数:", receipt["total_count"]);
            console.log("伝票合計:", receipt["amount"].toLocaleString() + "円");
            console.log("(うち消費税", receipt["tax"] + "）");
            console.log("----------------------------------------");
            if (receipt["is_invoice"]) {
                console.log("請求書払い");
            } else if (receipt["paid_at"] !== null) {
                console.log(
                    "合計額:",
                    receipt["amount"].toLocaleString() + "円"
                );
                console.log(
                    "お預り:",
                    receipt["payment"].toLocaleString() + "円"
                );
                console.log(
                    "お釣り:",
                    (receipt["payment"] - receipt["amount"]).toLocaleString() +
                        "円"
                );
            } else {
                console.log(
                    "未収額:",
                    (receipt["amount"] - receipt["payment"]).toLocaleString() +
                        "円"
                );
            }
            console.log("----------------------------------------");
            console.log("いつもご利用ありがとうございます。");
            console.log("今後ともよろしくお願いいたします。");
            if (receipt["paid_at"] === null) {
                console.log("未　収");
            }
            console.log("========================================");
            console.log("【生データ】");
            console.log(JSON.stringify(receipt, null, 2));
            console.log("========================================");
        },
    },
};
</script>
