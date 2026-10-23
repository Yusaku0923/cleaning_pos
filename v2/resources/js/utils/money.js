/**
 * 内税: 税込 amount に含まれる税額 (PHP の App\Domain\Money\PriceCalculator::includedTax と同じ計算)。
 * mode は config('pos.tax_rounding.invoice') ('round' | 'floor')。画面表示は請求書と同じ方式 (global-constraints)。
 *   round: amount - round(amount / (1 + rate))  (0.5 は 0 から遠い方へ。旧 請求書 PDF)
 *   floor: trunc(amount * rate / (100 + rate))   (旧 画面の Math.trunc(amount - amount / 1.1))
 * 浮動小数を使わず整数だけで計算する。
 */
export function includedTax(amount, ratePercent, mode) {
    const den = 100 + ratePercent;
    if (mode === 'round') {
        const num = amount * 100;
        const sign = num < 0 ? -1 : 1;
        return amount - sign * Math.floor((2 * Math.abs(num) + den) / (2 * den));
    }
    if (mode === 'floor') {
        return Math.trunc((amount * ratePercent) / den);
    }
    throw new Error(`mode must be 'round' or 'floor': '${mode}'`);
}

/**
 * 割引 (%) の値引額 (PHP の PriceCalculator::reduction と同じ: floor(amount × 割合 / 100))。
 * 整数の掛け算を先にするので浮動小数の誤差が出ない (旧の Math.floor(amount * (discount / 100)) は
 * 100 円 × 29% で 28 になるなど、まれに 1 円ずれていた)。
 */
export function discountReduction(amount, ratioPercent) {
    return Math.floor((amount * ratioPercent) / 100);
}
