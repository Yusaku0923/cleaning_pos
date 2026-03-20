<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDepartment;
use App\Models\DeliveryProduct;

class SKUniformSeeder extends Seeder
{
    public function run()
    {
        // 既に存在する場合はスキップ
        if (DeliveryCustomer::where('name', 'エスケーユニフォーム株式会社')->exists()) return;

        $customer = DeliveryCustomer::create([
            'name' => 'エスケーユニフォーム株式会社',
            'email' => null,
            'registration_number' => null,
        ]);

        $dept = $customer->departments()->create([
            'name' => '全般',
            'sort_order' => 1,
        ]);

        $products = [
            ['name' => 'つなぎ',       'unit_price' => 310,  'tax_rate' => 0.10, 'sort_order' => 1],
            ['name' => 'つなぎ（夏）',  'unit_price' => 310,  'tax_rate' => 0.10, 'sort_order' => 2],
            ['name' => 'つなぎ（修理）', 'unit_price' => 1000, 'tax_rate' => 0.10, 'sort_order' => 3],
            ['name' => 'ジャンバー',    'unit_price' => 310,  'tax_rate' => 0.10, 'sort_order' => 4],
        ];

        foreach ($products as $product) {
            $dept->products()->create($product);
        }
    }
}
