<?php

namespace App\Domain\Order;

use App\Models\Customer;
use App\Models\Manager;
use Carbon\CarbonImmutable;

/** 注文 1 件の登録内容 (StoreOrderRequest で検証済み)。金額は持たない (サーバーで計算する)。 */
final class StoreOrderData
{
    /**
     * @param  list<array{clothes_id:int, qty:int, issue_tag:bool}>  $items  画面で追加した順 (旧 indexes の順)
     * @param  CarbonImmutable|null  $orderedOn  注文日の指定 (旧 created_at。null は今)
     */
    public function __construct(
        public readonly Manager $manager,
        public readonly Customer $customer,
        public readonly array $items,
        public readonly ?int $discountRatio,
        public readonly ?int $discountAmount,
        public readonly int $deposit,
        public readonly bool $isInvoice,
        public readonly bool $notPaid,
        public readonly bool $checkReturn,
        public readonly ?CarbonImmutable $orderedOn,
    ) {}
}
