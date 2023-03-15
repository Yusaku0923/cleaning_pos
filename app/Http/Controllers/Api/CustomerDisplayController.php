<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\CustomerDisplay;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CustomerDisplayController extends Controller
{
    // broadcastはどのような形式で行うか？
    /**
     * 案1
     * 注文の状態を一つのjsonで管理
     * メリット：データの受け渡しは楽
     * デメリット：イベントの発火はめんどそう
     * 
     * 案2
     * イベントとそれに対応する内容を送信
     * メリット：フロント側でのイベント管理は楽そう
     * デメリット：状態の管理がダルい？
     * 
     * 案2で行く場合、イベント列挙
     * 
     * ・顧客設定
     * event: customer
     * value: 顧客名
     * display: 「いらっしゃいませ、{customer}様」
     * 
     * ・（注文作成開始）
     * event: order
     * value: (empty)
     * display: 注文選択画面に遷移
     * 
     * ・注文追加
     * event: update
     * value: {id: 商品ID, name: 商品名, price: 値段, count: 個数}
     * {"event":"add","id":"1","name":"Tシャツ","price": 100,"count": 1}
     * 
     * display: リストに商品追加、合計金額更新
     * 
     * ・注文削減
     * event: reduce
     * value: {id: 商品ID, count: 個数}
     * display: リストに商品削減、合計金額更新
     * 
     * ・割引
     * event: discount
     * value: {reduction: 値引き金額, percent: 値引き割合}
     * display: 合計金額から値引きの表示
     * 
     * ・お会計
     * event: account
     * value: {payment: お支払い金額, charge: お釣り}
     * display: お支払い金額とお釣りを表紙
     * 
     * ・終了
     * event: finish
     * value: (empty)
     * display: 「ありがとうございました。」→初期画面へ
     * 
     * ⇒一旦案2でやってみよ
     */
    public function broadcast(Request $request) {
        if (!(boolean)Auth::user()->is_invoice) {
            broadcast(new CustomerDisplay($request->all()));
        }

            return response()->json([]);
    }
}
