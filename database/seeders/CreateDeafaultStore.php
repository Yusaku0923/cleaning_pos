<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\Tax;

class CreateDeafaultStore extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Store::query()->create([
            'name' => 'てすとクリーニング',
            'email' => 'test@gmail.com',
            'password' => Hash::make('Test123456'),
            'address' => '東京',
        ]);

        Tax::query()->create([
            'store_id' => 1,
            'tax' => 10,
        ]);
    }
}
