<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Manager;
use App\Models\SerialNumber;

class CreateDefaultManager extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $managers = [
            [
                'name' => '担当者1',
            ],
            [
                'name' => '担当者2',
            ],
            [
                'name' => '担当者3',
            ],
        ];
        foreach ($managers as $arr) {
            $obj = Manager::query()->create([
                'store_id' => 1,
                'name' => $arr['name'],
            ]);

            SerialNumber::query()->create([
                'manager_id' => $obj->id,
                'serial_numbers' => '0000'
            ]);
        }
    }
}
