<?php

namespace App\Http\Requests;

use App\Domain\Order\StoreOrderData;
use App\Models\Clothes;
use App\Models\Customer;
use App\Models\Manager;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * POST /api/orders。担当者・顧客・商品は自店舗のもの (スコープ付きで見つかること) に限る。
 * 金額 (合計) は受け取らない。items[].price は送られても使わない。
 */
class StoreOrderRequest extends FormRequest
{
    private ?Manager $manager = null;

    private ?Customer $customer = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manager_id' => ['required', 'integer'],
            'customer_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.clothes_id' => ['required', 'integer', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.price' => ['nullable', 'integer'],
            'items.*.issue_tag' => ['sometimes', 'boolean'],
            'discount_ratio' => ['nullable', 'integer', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'deposit' => ['required', 'integer', 'min:0', 'max:9999999'],
            'is_invoice' => ['required', 'boolean'],
            'not_paid' => ['sometimes', 'boolean'],
            'check_return' => ['sometimes', 'boolean'],
            'created_at' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $this->manager = Manager::find((int) $this->input('manager_id'));
            if ($this->manager === null) {
                $v->errors()->add('manager_id', '担当者が見つかりません。');
            }
            $this->customer = Customer::find((int) $this->input('customer_id'));
            if ($this->customer === null || ($this->manager !== null && (int) $this->customer->manager_id !== $this->manager->id)) {
                $v->errors()->add('customer_id', '顧客が見つかりません。');
            } elseif ($this->boolean('is_invoice') && ! $this->customer->is_invoice) {
                $v->errors()->add('is_invoice', '請求書払いの顧客ではありません。');
            }

            $items = $this->input('items');
            $found = Clothes::whereIn('id', array_map(fn ($i) => (int) $i['clothes_id'], $items))->pluck('id')->all();
            foreach ($items as $i => $item) {
                if (! in_array((int) $item['clothes_id'], $found, true)) {
                    $v->errors()->add("items.{$i}.clothes_id", '商品が見つかりません。');
                }
            }
        }];
    }

    public function toData(): StoreOrderData
    {
        return new StoreOrderData(
            manager: $this->manager,
            customer: $this->customer,
            items: array_values(array_map(fn ($i) => [
                'clothes_id' => (int) $i['clothes_id'],
                'qty' => (int) $i['qty'],
                'issue_tag' => filter_var($i['issue_tag'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ], $this->input('items'))),
            discountRatio: $this->filled('discount_ratio') ? (int) $this->input('discount_ratio') : null,
            discountAmount: $this->filled('discount_amount') ? (int) $this->input('discount_amount') : null,
            deposit: (int) $this->input('deposit'),
            isInvoice: $this->boolean('is_invoice'),
            notPaid: $this->boolean('not_paid'),
            checkReturn: $this->boolean('check_return'),
            orderedOn: $this->filled('created_at') ? CarbonImmutable::createFromFormat('!Y-m-d', $this->input('created_at')) : null,
        );
    }
}
