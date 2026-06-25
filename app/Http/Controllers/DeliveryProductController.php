<?php

namespace App\Http\Controllers;

use App\Models\DeliveryCustomer;
use App\Models\DeliveryProduct;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryProductController extends Controller
{
    /** 許可する税率（10% / 8%軽減税率） */
    private const TAX_RATES = ['0.10', '0.08'];

    /** 顧客ごとの商品一覧 */
    public function index(DeliveryCustomer $customer)
    {
        $departments = $customer->departments()->with('products')->orderBy('sort_order')->get();
        return view('delivery.products.index', compact('customer', 'departments'));
    }

    /** 追加フォーム */
    public function create(DeliveryCustomer $customer, Request $request)
    {
        $departments = $customer->departments()->orderBy('sort_order')->get();
        $product = new DeliveryProduct(['delivery_department_id' => $request->department_id]);
        return view('delivery.products.form', compact('customer', 'departments', 'product'));
    }

    /** 追加実行 */
    public function store(Request $request, DeliveryCustomer $customer)
    {
        $data = $this->validateProduct($request, $customer);

        if (!isset($data['sort_order'])) {
            $data['sort_order'] = DeliveryProduct::where('delivery_department_id', $data['delivery_department_id'])
                ->max('sort_order') + 1;
        }

        DeliveryProduct::create($data);

        return redirect()
            ->route('delivery.products.index', $customer)
            ->with('message', '商品を追加しました');
    }

    /** 編集フォーム */
    public function edit(DeliveryCustomer $customer, DeliveryProduct $product)
    {
        $this->ensureProductBelongsToCustomer($product, $customer);

        $departments = $customer->departments()->orderBy('sort_order')->get();
        return view('delivery.products.form', compact('customer', 'departments', 'product'));
    }

    /** 編集実行 */
    public function update(Request $request, DeliveryCustomer $customer, DeliveryProduct $product)
    {
        $this->ensureProductBelongsToCustomer($product, $customer);

        $data = $this->validateProduct($request, $customer);
        $product->update($data);

        return redirect()
            ->route('delivery.products.index', $customer)
            ->with('message', '商品を更新しました');
    }

    /** バリデーション。部署はその顧客に属するもののみ許可。 */
    private function validateProduct(Request $request, DeliveryCustomer $customer): array
    {
        return $request->validate([
            'delivery_department_id' => [
                'required',
                Rule::exists('delivery_departments', 'id')->where('delivery_customer_id', $customer->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'tax_rate' => ['required', Rule::in(self::TAX_RATES)],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    /** 商品が顧客の部署に属さなければ404 */
    private function ensureProductBelongsToCustomer(DeliveryProduct $product, DeliveryCustomer $customer): void
    {
        abort_unless(
            $product->department && $product->department->delivery_customer_id === $customer->id,
            404
        );
    }
}
