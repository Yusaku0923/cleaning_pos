<?php

namespace App\Domain\Money;

use InvalidArgumentException;

/**
 * 金額の計算。すべて整数 (円) で、浮動小数を使わない。
 * 旧: OrderComponent.vue (合計 = 単価の積み上げ - 値引)、DiscountModalComponent.vue
 * (割引額 = floor(合計 × 割合 / 100))、DeliveryNoteController::calcTax (外税 floor)、
 * invoice/pdf.blade.php (内税 = 合計 - round(合計 / 1.1))。
 * 端数の方式は呼ぶ側が config('pos.tax_rounding.invoice'|'delivery') を渡す。
 */
class PriceCalculator
{
    public const ROUND = 'round';

    public const FLOOR = 'floor';

    public function lineTotal(int $unitPrice, int $qty): int
    {
        return $unitPrice * $qty;
    }

    /**
     * @param  array<int|array{unit_price:int, qty:int}>  $lines  行の金額 (int) か ['unit_price' => , 'qty' => ]
     * @param  int|null  $discountRatio  割引 % (0〜100)。指定があればこちらから値引額を出す
     * @param  int|null  $discountAmount  値引額 (円)。割合がないときに使う
     */
    public function orderTotal(array $lines, ?int $discountRatio, ?int $discountAmount): int
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $subtotal += is_int($line) ? $line : $this->lineTotal((int) $line['unit_price'], (int) $line['qty']);
        }

        return $subtotal - $this->reduction($subtotal, $discountRatio, $discountAmount);
    }

    /** 値引額 (旧 orders.reduction)。 */
    public function reduction(int $subtotal, ?int $discountRatio, ?int $discountAmount): int
    {
        if ($discountRatio !== null && ($discountRatio < 0 || $discountRatio > 100)) {
            throw new InvalidArgumentException("discount ratio must be 0..100: {$discountRatio}");
        }
        if ($discountRatio) {
            return $this->divFloor($subtotal * $discountRatio, 100);
        }
        $amount = $discountAmount ?? 0;
        if ($amount < 0 || $amount > max($subtotal, 0)) {
            throw new InvalidArgumentException("discount amount must be 0..{$subtotal}: {$amount}");
        }

        return $amount;
    }

    /** 外税: 税抜 $amount にかかる税額。 */
    public function tax(int $amount, int $ratePercent, string $mode): int
    {
        return $this->divide($amount * $ratePercent, 100, $mode);
    }

    /**
     * 内税: 税込 $amount に含まれる税額。
     * round = 旧 請求書 PDF (amount - round(amount / (1 + rate)))、floor = 旧 画面 (Math.trunc(amount - amount / (1 + rate)))。
     * 'floor' はここだけ 0 に向かう切り捨て (外税の tax() は旧 納品書と同じく PHP の floor)。
     */
    public function includedTax(int $amount, int $ratePercent, string $mode): int
    {
        $num = $amount * $ratePercent;
        $den = 100 + $ratePercent;

        return match ($this->mode($mode)) {
            // amount - round(amount*100/den) を、税額側で四捨五入の向きを合わせて整数だけで計算
            self::ROUND => $amount - $this->divRoundHalfUp($amount * 100, $den),
            // 旧 画面は Math.trunc (0 に向かって切り捨て)。負の金額で floor と違う
            self::FLOOR => intdiv($num, $den),
        };
    }

    private function divide(int $num, int $den, string $mode): int
    {
        return match ($this->mode($mode)) {
            self::ROUND => $this->divRoundHalfUp($num, $den),
            self::FLOOR => $this->divFloor($num, $den),
        };
    }

    private function mode(string $mode): string
    {
        if ($mode !== self::ROUND && $mode !== self::FLOOR) {
            throw new InvalidArgumentException("mode must be 'round' or 'floor': '{$mode}'");
        }

        return $mode;
    }

    private function divFloor(int $num, int $den): int
    {
        $q = intdiv($num, $den);

        return ($num % $den !== 0 && ($num < 0) !== ($den < 0)) ? $q - 1 : $q;
    }

    /** PHP の round() と同じく 0.5 は 0 から遠い方へ。 */
    private function divRoundHalfUp(int $num, int $den): int
    {
        $sign = ($num < 0) !== ($den < 0) ? -1 : 1;

        return $sign * intdiv(2 * abs($num) + abs($den), 2 * abs($den));
    }
}
