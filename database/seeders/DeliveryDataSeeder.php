<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDepartment;
use App\Models\DeliveryProduct;

class DeliveryDataSeeder extends Seeder
{
    public function run()
    {
        // 既にデータが存在する場合はスキップ
        if (DeliveryCustomer::count() > 0) return;

        $customer = DeliveryCustomer::create([
            'name' => '東急リネンサプライ株式会社',
            'email' => null,           // 実際のメールアドレスを設定してください
            'registration_number' => null,  // 実際の登録番号を設定してください
        ]);

        $departments = [
            [
                'name' => 'モンリク',
                'sort_order' => 4,
                'products' => [
                    ['name' => '作業服上',       'unit_price' => 80,  'tax_rate' => 0.10, 'sort_order' => 1],
                    ['name' => '作業服下',       'unit_price' => 80,  'tax_rate' => 0.10, 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'NBSロジソル',
                'sort_order' => 3,
                'products' => [
                    ['name' => '作業服上',       'unit_price' => 80,  'tax_rate' => 0.10, 'sort_order' => 1],
                    ['name' => '作業服下',       'unit_price' => 80,  'tax_rate' => 0.10, 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'サッポログループ物流',
                'sort_order' => 2,
                'products' => [
                    ['name' => '作業服上',       'unit_price' => 80,  'tax_rate' => 0.10, 'sort_order' => 1],
                    ['name' => '作業服下',       'unit_price' => 125,  'tax_rate' => 0.10, 'sort_order' => 2],
                    ['name' => 'ジャンパー',      'unit_price' => 200,  'tax_rate' => 0.10, 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'サッポロビール日田工場',
                'sort_order' => 1,
                'products' => [
                    ['name' => '作業服上',       'unit_price' => 80,  'tax_rate' => 0.10, 'sort_order' => 1],
                    ['name' => '作業服下',       'unit_price' => 80,  'tax_rate' => 0.10, 'sort_order' => 2],
                    ['name' => '作業服上（特洗）', 'unit_price' => 250, 'tax_rate' => 0.10, 'sort_order' => 3],
                    ['name' => '作業服下（特洗）', 'unit_price' => 250, 'tax_rate' => 0.10, 'sort_order' => 4],
                    ['name' => '防塵服',         'unit_price' => 150,     'tax_rate' => 0.10, 'sort_order' => 5],
                ],
            ],
        ];

        foreach ($departments as $deptData) {
            $products = $deptData['products'];
            unset($deptData['products']);
            $dept = $customer->departments()->create($deptData);
            foreach ($products as $product) {
                $dept->products()->create($product);
            }
        }
    }
}
