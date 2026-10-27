<?php

namespace App\Http\Controllers\Api;

use App\Domain\Order\OrderQueries;
use App\Domain\Order\OrderService;
use App\Domain\Receipt\ReceiptData;
use App\Domain\Tag\TagNumber;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** 旧 Api\OrdersController の store / search / payment / delete / updateTag / fetchReceiptInfo。 */
class OrderController extends Controller
{
    /** 検索条件の見出し (旧 CONDITION_KEY_JP / CONDITION_VALUE_JP と同じ) */
    private const CONDITION_KEY_JP = [
        'order_id' => '伝票番号',
        'term' => '期間',
        'tag' => 'タグ',
        'has_paid' => 'お支払い済み',
        'has_handed' => 'お渡し済み',
    ];

    private const CONDITION_VALUE_JP = [
        'neither' => '指定しない',
        'paid' => 'はい',
        'unpaid' => 'いいえ',
        'handed' => 'はい',
        'unhanded' => 'いいえ',
    ];

    /** POST /api/orders → 201 {order_id, tags, total}。total はサーバーで計算した合計。 */
    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $order = $orders->store($request->toData());

        return response()->json([
            'order_id' => $order->id,
            'tags' => $order->items->pluck('tag')->reject(fn ($t) => $t === OrderService::NO_TAG)->values()->all(),
            'total' => (int) $order->amount,
        ], 201);
    }

    /** GET /api/receipt/{order} → レシート印刷用データ (旧と同じキー)。他店舗の注文は 404。 */
    public function receipt(string $order): JsonResponse
    {
        if (! ctype_digit($order)) {
            abort(404);
        }
        $model = Order::find((int) $order);
        if ($model === null) {
            return response()->json(['error' => '注文が見つかりません'], 404);
        }

        $receipt = ReceiptData::forOrder($model);
        if ($receipt === null) {
            return response()->json(['error' => '顧客が見つかりません'], 422);
        }

        return response()->json($receipt);
    }

    /**
     * POST /api/order/search {customer_id: int|null, conditions: {...}} → {orders, conditions}
     * customer_id null = 全体検索。注文は自店舗のものだけ (他店舗の顧客 ID を送っても何も出ない)。
     * conditions は画面に出す「現在の検索項目」(旧と同じ見出し・値)。
     */
    public function search(Request $request, OrderQueries $queries): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'conditions' => ['present', 'array'],
            'conditions.order_id' => ['nullable', 'integer'],
            'conditions.after' => ['nullable', 'date_format:Y-m-d'],
            'conditions.before' => ['nullable', 'date_format:Y-m-d'],
            'conditions.tag' => ['nullable', 'string', 'max:10'],
            'conditions.has_paid' => ['nullable', 'in:neither,paid,unpaid'],
            'conditions.has_handed' => ['nullable', 'in:neither,handed,unhanded'],
        ]);
        $where = $data['conditions'] ?? [];
        $customerId = isset($data['customer_id']) ? (int) $data['customer_id'] : null;

        return response()->json([
            'orders' => $queries->fetchOrders($customerId, -1, $where),
            'conditions' => self::describe($where),
        ]);
    }

    /** 旧 search の conditions 組み立てと同じ (送られた順に見出しを並べる。期間は after / before をまとめて 1 行) */
    private static function describe(array $where): array
    {
        $out = [];
        foreach ($where as $key => $val) {
            switch ($key) {
                case 'has_paid':
                case 'has_handed':
                    if (! empty($val) && $val !== 'neither') {
                        $out[self::CONDITION_KEY_JP[$key]] = self::CONDITION_VALUE_JP[$val];
                    }
                    break;
                case 'after':
                case 'before':
                    if (! isset($out['期間']) && (! empty($where['after']) || ! empty($where['before']))) {
                        $out['期間'] = (empty($where['after']) ? 'なし' : date('Y/m/d', strtotime($where['after'])))
                            .' ～ '
                            .(empty($where['before']) ? 'なし' : date('Y/m/d', strtotime($where['before'])));
                    }
                    break;
                default:
                    if (! empty($val) && isset(self::CONDITION_KEY_JP[$key])) {
                        $out[self::CONDITION_KEY_JP[$key]] = $val;
                    }
                    break;
            }
        }

        return $out;
    }

    /** POST /api/payment {order_id, payment} → {ok: true}。お預り金額と入金日時 (今) を入れる。他店舗の注文は 404。 */
    public function payment(Request $request, OrderService $orders): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'payment' => ['required', 'integer', 'min:0'],
        ]);
        $orders->pay(Order::findOrFail((int) $data['order_id']), (int) $data['payment']);

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/order/delete {order_id, customer_id} → {ok: true}。他店舗の注文は 404。
     * 顧客の集計は送られた customer_id ではなく注文の顧客で戻す (OrderService::delete)。
     */
    public function delete(Request $request, OrderService $orders): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
        ]);
        $order = Order::find((int) $data['order_id']);
        if ($order === null) {
            return response()->json(['error' => '注文が見つかりません'], 404);
        }
        $orders->delete($order);

        return response()->json(['ok' => true]);
    }

    /** POST /api/order/update_tag {id: 品物ID, tag: 'X-XXX'} → {ok: true}。タグは 1-000〜10-999。他店舗の品物は 404。 */
    public function updateTag(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'tag' => ['required', 'string', function ($attr, $value, $fail) {
                try {
                    TagNumber::fromString((string) $value);
                } catch (\InvalidArgumentException) {
                    $fail('タグ番号は 1-000〜10-999 で入力してください。');
                }
            }],
        ]);
        $item = OrderItem::findOrFail((int) $data['id']);
        $item->tag = TagNumber::fromString($data['tag'])->toString();
        $item->save();

        return response()->json(['ok' => true]);
    }
}
