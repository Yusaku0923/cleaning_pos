<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            'password' => \Hash::make('Test123456'),
            'address' => '東京'
        ]);
    }
}
