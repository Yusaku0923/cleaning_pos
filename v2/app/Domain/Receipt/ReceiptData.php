<?php

namespace App\Domain\Receipt;

use App\Domain\Tag\TagNumber;
use App\Models\Clothes;
use App\Models\Customer;
use App\Models\Manager;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Store;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonSerializable;

/**
 * レシート (お預り票) の印刷用データ。GET /api/receipt/{order} の JSON。
 * 旧 Api\OrdersController::fetchReceiptInfo と Order::fetchReciptDetail を写したもの。
 * 印刷側 (receiptCommands.js) を旧と同じに保つため、キー名・値は旧のまま (R3):
 *   - discount = orders.discount (割引の割合 %)。旧の印刷は「小計 = amount + discount」と計算する
 *   - discount_raito = 旧の綴りのまま。旧 DB にカラムが無く常に null
 *   - tax = taxes.tax (税率の文字列。旧の印刷は「(うち消費税 10）」と税率をそのまま出す)
 *   - is_invoice = 0/1、paid_at = 'Y-m-d H:i:s' か null (旧は DB の生の値)
 *   - ip_address = プリンタの IP (旧 ipaddresses.id=1 → v2 は settings の printer_ip)
 */
final class ReceiptData implements JsonSerializable
{
    /** @param array<string, mixed> $data */
    private function __construct(private readonly array $data) {}

    /** 顧客が見つからない (論理削除済み) ときは null (旧は 422)。 */
    public static function forOrder(Order $order): ?self
    {
        $customer = Customer::find($order->customer_id);
        if ($customer === null) {
            return null;
        }

        $store = Store::find($order->store_id);
        $managerName = Manager::where('id', $customer->manager_id)->value('name');
        $tax = Tax::where('store_id', $order->store_id)->value('tax');
        [$orders, $totalCount] = self::orderList((int) $order->id);

        return new self([
            'store_name' => $store?->name,
            'store_address' => $store?->address,
            'store_tel' => $store?->phone_number,
            'customer_name_kana' => $customer->name_kana,
            'customer_name' => $customer->name,
            'customer_tel' => $customer->phone_number,
            'manager_name' => $managerName,
            'ordered_at' => $order->created_at->format('Y年m月d日 H時i分'),
            'order_list' => $orders,
            'total_count' => $totalCount,
            'amount' => (int) $order->amount,
            'discount' => (int) $order->discount,
            'discount_raito' => null,
            'payment' => (int) $order->payment,
            'tax' => $tax,
            'paid_at' => $order->paid_at?->format('Y-m-d H:i:s'),
            'is_invoice' => $order->is_invoice ? 1 : 0,
            'ip_address' => Setting::get('printer_ip'),
        ]);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    /**
     * 明細 (旧 Order::fetchReciptDetail)。
     * - タグ 1 枚の商品: 同じ商品はまとめて 1 行 (count・合計金額、tag_start〜tag_end)
     * - タグ複数の商品 (tag_count > 1): 同じ商品でもタグが続いていれば 1 着ずつの行
     * - clothes_id 999 (2 枚目以降のタグ) は直前の行の tag_end を伸ばす
     * 最後に tag_start の順に並べる。
     *
     * @return array{0: list<array{id:int, name:string, tag_start:string, tag_end:string, price:int, count:int}>, 1: int}
     */
    public static function orderList(int $orderId): array
    {
        $list = DB::table('order_items')
            ->select('order_items.*', 'clothes.name_kana', 'clothes.price', 'clothes.tag_count')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('clothes', 'order_items.clothes_id', '=', 'clothes.id')
            ->where('order_items.order_id', $orderId)
            ->whereNull('order_items.deleted_at')
            ->orderBy('order_items.id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        usort($list, fn ($a, $b) => self::compareTags($a['tag'], $b['tag']));

        $result = [];
        $totalCount = 0;
        foreach ($list as $row) {
            if ((int) $row['clothes_id'] === Clothes::MULTI_TAG_CLOTHES_ID && count($result) > 0) {
                $last = count($result) - 1;
                if (self::compareTags($row['tag'], $result[$last]['tag_end']) > 0) {
                    $result[$last]['tag_end'] = $row['tag'];
                }
            } else {
                $lastIndex = null;
                for ($j = count($result) - 1; $j >= 0; $j--) {
                    if ($result[$j]['id'] == $row['clothes_id']) {
                        $lastIndex = $j;
                        break;
                    }
                }
                $tagCount = isset($row['tag_count']) ? (int) $row['tag_count'] : 1;
                $line = [
                    'id' => (int) $row['clothes_id'],
                    'name' => $row['name_kana'],
                    'tag_start' => $row['tag'],
                    'tag_end' => $row['tag'],
                    'price' => (int) $row['price'],
                    'count' => 1,
                ];

                if ($tagCount > 1 && $lastIndex !== null && self::isConsecutiveTag($result[$lastIndex]['tag_end'], $row['tag'])) {
                    $result[] = $line;
                } else {
                    $index = array_search($row['clothes_id'], array_column($result, 'id'));
                    if ($index === false) {
                        $result[] = $line;
                    } else {
                        $result[$index]['count']++;
                        if (self::compareTags($row['tag'], $result[$index]['tag_start']) < 0) {
                            $result[$index]['tag_start'] = $row['tag'];
                        }
                        if (self::compareTags($row['tag'], $result[$index]['tag_end']) > 0) {
                            $result[$index]['tag_end'] = $row['tag'];
                        }
                        $result[$index]['price'] += (int) $row['price'];
                    }
                }
            }
            $totalCount++;
        }

        usort($result, fn ($a, $b) => self::compareTags($a['tag_start'], $b['tag_start']));

        return [$result, $totalCount];
    }

    /**
     * タグの比較 (旧 Order::compareTags をそのまま)。'X-XXX' の前と後ろを数で比べる。
     * 旧の「9-999 が最大、次は 0-001」の扱いも旧のまま残す (並び順を旧と同じにするため)。
     */
    private static function compareTags(string|int $tagA, string|int $tagB): int
    {
        if (is_numeric($tagA)) {
            $tagA = self::formatTagFromNumber($tagA);
        }
        if (is_numeric($tagB)) {
            $tagB = self::formatTagFromNumber($tagB);
        }

        $partsA = explode('-', (string) $tagA);
        $partsB = explode('-', (string) $tagB);
        if (count($partsA) !== 2 || count($partsB) !== 2) {
            return strcmp((string) $tagA, (string) $tagB);
        }

        $firstA = (int) $partsA[0];
        $firstB = (int) $partsB[0];
        $secondA = (int) $partsA[1];
        $secondB = (int) $partsB[1];

        $isMaxA = ($firstA === 9 && $secondA === 999);
        $isMaxB = ($firstB === 9 && $secondB === 999);
        $isMinA = ($firstA === 0 && $secondA === 1);
        $isMinB = ($firstB === 0 && $secondB === 1);

        if ($isMaxA && $isMaxB) {
            return 0;
        }
        if ($isMinA && $isMinB) {
            return 0;
        }
        if ($isMaxA && $isMinB) {
            return -1;
        }
        if ($isMinA && $isMaxB) {
            return 1;
        }
        if ($isMaxA && ! $isMinB) {
            return 1;
        }
        if ($isMaxB && ! $isMinA) {
            return -1;
        }
        if ($isMinA) {
            if ($isMaxB) {
                return 1;
            }
            if ($firstB >= 1) {
                return -1;
            }
        }
        if ($isMinB) {
            if ($isMaxA) {
                return -1;
            }
            if ($firstA >= 1) {
                return 1;
            }
        }

        if ($firstA !== $firstB) {
            return $firstA <=> $firstB;
        }

        return $secondA <=> $secondB;
    }

    /** 旧 Order::formatTagFromNumber (8873 → '8-873')。 */
    private static function formatTagFromNumber(string|int $tag): string
    {
        $s = (string) $tag;
        if ((int) $tag >= 10000) {
            $first = substr($s, 0, 2);
            $second = (int) substr($s, 2);
        } else {
            $first = substr($s, 0, 1);
            $second = (int) substr($s, 1);
        }

        return $first.'-'.str_pad((string) $second, 3, '0', STR_PAD_LEFT);
    }

    /**
     * $tagB が $tagA のすぐ次か。タグ番号どうしなら TagNumber の判定 (10999 → 1000 も連続。e2e/allowed-diffs.md)。
     * 0-000 (タグなし) などタグ番号でない値は旧と同じく「X*1000+XXX の差が 1」で判定する。
     */
    private static function isConsecutiveTag(string $tagA, string $tagB): bool
    {
        try {
            return TagNumber::fromString($tagA)->isFollowedBy(TagNumber::fromString($tagB));
        } catch (InvalidArgumentException) {
            return self::tagToNumber($tagB) - self::tagToNumber($tagA) === 1;
        }
    }

    private static function tagToNumber(string $tag): int
    {
        if (is_numeric($tag)) {
            return (int) $tag;
        }
        $parts = explode('-', $tag);

        return count($parts) === 2 ? (int) $parts[0] * 1000 + (int) $parts[1] : 0;
    }
}
