<?php

namespace Tests\Unit;

use App\Domain\Money\PriceCalculator;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * 金額 (旧 OrderComponent.vue / DiscountModalComponent.vue / DeliveryNoteController::calcTax / invoice/pdf.blade.php)。
 */
class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new PriceCalculator;
    }

    public function test_tax_rounding_modes(): void
    {
        $this->assertSame(101, $this->calc->tax(1005, 10, 'round'));
        $this->assertSame(100, $this->calc->tax(1005, 10, 'floor'));
        $this->assertSame(100, $this->calc->tax(1004, 10, 'round'));
        $this->assertSame(0, $this->calc->tax(0, 10, 'round'));
        $this->assertSame(64, $this->calc->tax(805, 8, 'floor'));  // 64.4
        $this->assertSame(64, $this->calc->tax(805, 8, 'round'));
        $this->assertSame(65, $this->calc->tax(807, 8, 'round'));  // 64.56
    }

    public function test_tax_rejects_unknown_mode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calc->tax(100, 10, 'ceil');
    }

    public function test_included_tax_matches_legacy_screens(): void
    {
        // 旧 請求書 PDF: amount - round(amount / 1.1)  /  旧 画面: trunc(amount - amount / 1.1)
        $this->assertSame(45, $this->calc->includedTax(500, 10, 'round'));
        $this->assertSame(45, $this->calc->includedTax(500, 10, 'floor'));
        $this->assertSame(46, $this->calc->includedTax(505, 10, 'round'));
        $this->assertSame(45, $this->calc->includedTax(505, 10, 'floor'));
        $this->assertSame(0, $this->calc->includedTax(0, 10, 'round'));
    }

    public function test_negative_amounts(): void
    {
        // 外税: round は PHP round (0 から遠い方)、floor は PHP floor (旧 納品書 floor($subtotal * $rate))
        $this->assertSame(-101, $this->calc->tax(-1005, 10, 'round'));  // round(-100.5)
        $this->assertSame(-101, $this->calc->tax(-1005, 10, 'floor'));  // floor(-100.5)
        $this->assertSame(-100, $this->calc->tax(-1004, 10, 'round'));  // round(-100.4)
        $this->assertSame(-101, $this->calc->tax(-1004, 10, 'floor'));  // floor(-100.4)
        // 内税: round = 旧 請求書 PDF amount - round(amount / 1.1)、floor = 旧 画面 Math.trunc (0 に向かって切り捨て)
        $this->assertSame(-46, $this->calc->includedTax(-505, 10, 'round'));  // -505 - round(-459.09)
        $this->assertSame(-45, $this->calc->includedTax(-505, 10, 'floor'));  // trunc(-45.909)
        $this->assertSame(-45, $this->calc->includedTax(-500, 10, 'floor'));  // trunc(-45.45)
    }

    public function test_line_total(): void
    {
        $this->assertSame(1500, $this->calc->lineTotal(500, 3));
        $this->assertSame(0, $this->calc->lineTotal(500, 0));
    }

    public function test_order_total_without_discount(): void
    {
        $lines = [['unit_price' => 500, 'qty' => 2], ['unit_price' => 330, 'qty' => 1]];
        $this->assertSame(1330, $this->calc->orderTotal($lines, null, null));
        $this->assertSame(1330, $this->calc->orderTotal([1000, 330], null, null));
    }

    public function test_order_total_with_discount_ratio_floors_the_reduction(): void
    {
        // 旧 calcReduction: reduction = Math.floor(amount * (discount / 100))
        $lines = [['unit_price' => 1333, 'qty' => 1]];
        $this->assertSame(1333 - 133, $this->calc->orderTotal($lines, 10, null));
        $this->assertSame(0, $this->calc->orderTotal($lines, 100, null));
        // 割合があるときは保存済みの値引額より割合を使う (旧は割合から値引額を計算)
        $this->assertSame(1333 - 133, $this->calc->orderTotal($lines, 10, 133));
    }

    public function test_order_total_with_discount_amount(): void
    {
        $this->assertSame(800, $this->calc->orderTotal([1000], null, 200));
        $this->assertSame(800, $this->calc->orderTotal([1000], 0, 200));
    }

    public function test_order_total_rejects_out_of_range_discount(): void
    {
        foreach ([[101, null], [-1, null], [null, -1], [null, 1001]] as [$ratio, $amount]) {
            try {
                $this->calc->orderTotal([1000], $ratio, $amount);
                $this->fail('should be rejected');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
