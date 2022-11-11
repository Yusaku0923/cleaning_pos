<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Clothes;

class CreateSampleCategories extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $list = [
            'シャツ' =>[
                [
                    'name' => 'Yシャツ（立体仕上げ）',
                    'short_name' => 'Yｼｬﾂ',
                    'price' => 170
                ],
                [
                    'name' => 'Yシャツ（色柄立体仕上げ）',
                    'short_name' => 'Yｼｬﾂ（色柄立体仕上げ）',
                    'price' => 230
                ],
                [
                    'name' => 'Yシャツ（たたみ仕上げ）',
                    'short_name' => 'Yｼｬﾂ（たたみ仕上げ）',
                    'price' => 190
                ],
                [
                    'name' => 'デザインYシャツ',
                    'short_name' => 'デザインYシャツ',
                    'price' => 270
                ],
                [
                    'name' => 'Yシャツ（手仕上げ）',
                    'short_name' => 'Yシャツ（手仕上げ）',
                    'price' => 360
                ],
                [
                    'name' => 'シャツ（立体仕上げ）',
                    'short_name' => 'シャツ（立体仕上げ）',
                    'price' => 430
                ],
                [
                    'name' => 'ブラウス',
                    'short_name' => 'ﾌﾞﾗｳｽ',
                    'price' => 430
                ],
            ],
            '紳士服' => [
                [
                    'name' => '紳士上着',
                    'short_name' => 'ｼﾝｼｳﾜｷﾞ',
                    'price' => 550,
                ],
                [
                    'name' => 'ベスト',
                    'short_name' => 'ﾍﾞｽﾄ',
                    'price' => 430,
                ],
                [
                    'name' => 'ズボン',
                    'short_name' => 'ｽﾞﾎﾞﾝ',
                    'price' => 430,
                ],
                [
                    'name' => 'ネクタイ',
                    'short_name' => 'ﾈｸﾀｲ',
                    'price' => 380,
                ],
            ],
            '婦人服' => [
                [
                    'name' => '婦人上着',
                    'short_name' => 'ﾌｼﾞﾝｳﾜｷﾞ',
                    'price' => 550,
                ],
                [
                    'name' => 'パンツ',
                    'short_name' => 'ﾊﾟﾝﾂ',
                    'price' => 430,
                ],
                [
                    'name' => 'スカート',
                    'short_name' => 'スカート',
                    'price' => 430,
                ],
                [
                    'name' => 'ワンピース',
                    'short_name' => 'ワンピース',
                    'price' => 770,
                ],
            ],
            'ジャンパー･コート' => [
                [
                    'name' => 'ジャンパー',
                    'short_name' => 'ジャンパー',
                    'price' => 710,
                ],
                [
                    'name' => 'ハーフコート',
                    'short_name' => 'ハーフコート',
                    'price' => 830,
                ],
                [
                    'name' => 'コート',
                    'short_name' => 'コート',
                    'price' => 930,
                ],
                [
                    'name' => 'ダウンジャンパー',
                    'short_name' => 'ダウンジャンパー',
                    'price' => 1800,
                ],
                [
                    'name' => 'ダウンハーフコート',
                    'short_name' => 'ダウンハーフコート',
                    'price' => 1900,
                ],
            ],
            'セーター' => [
                [
                    'name' => 'セーター',
                    'short_name' => 'セーター',
                    'price' => 430
                ],
                [
                    'name' => 'カーディガン',
                    'short_name' => 'カーディガン',
                    'price' => 430
                ],
            ],
            '制服' => [
                [
                    'name' => '学生服（上）',
                    'short_name' => '学生服（上）',
                    'price' => 550,
                ],
                [
                    'name' => 'セーラー服（上）',
                    'short_name' => 'セーラー服（上）',
                    'price' => 550,
                ],
                [
                    'name' => '学生ズボン',
                    'short_name' => '学生ズボン',
                    'price' => 430,
                ],
                [
                    'name' => '学生ヒダスカート',
                    'short_name' => '学生ヒダスカート',
                    'price' => 480,
                ],
            ],
            '幼児（サイズ 130ｃｍまで）' => [
                [
                    'name' => 'シャツ',
                    'short_name' => 'シャツ',
                    'price' => 380,
                ],
                [
                    'name' => 'セーター',
                    'short_name' => 'セーター',
                    'price' => 380,
                ],
                [
                    'name' => '上着',
                    'short_name' => '上着',
                    'price' => 380,
                ],
                [
                    'name' => 'ズボン',
                    'short_name' => 'ズボン',
                    'price' => 380,
                ],
                [
                    'name' => 'スカート',
                    'short_name' => 'スカート',
                    'price' => 380,
                ],
                [
                    'name' => 'ジャンパー',
                    'short_name' => 'ジャンパー',
                    'price' => 380,
                ],
                [
                    'name' => 'コート',
                    'short_name' => 'コート',
                    'price' => 380,
                ],
            ],
            '白物・その他' => [
                [
                    'name' => '白衣（長）',
                    'short_name' => '白衣（長）',
                    'price' => 430,
                ],
                [
                    'name' => '白衣ワンピース',
                    'short_name' => '白衣ワンピース',
                    'price' => 480,
                ],
                [
                    'name' => 'ハッピ',
                    'short_name' => 'ハッピ',
                    'price' => 450,
                ],
                [
                    'name' => 'ゆかた（機械仕上げ）',
                    'short_name' => 'ゆかた（機械仕上げ）',
                    'price' => 1200,
                ],
                [
                    'name' => 'ゆかた（手仕上げ）',
                    'short_name' => 'ゆかた（手仕上げ）',
                    'price' => 2000,
                ],
            ],
            '小物' => [
                [
                    'name' => '野球帽',
                    'short_name' => '野球帽',
                    'price' => 420,
                ],
                [
                    'name' => '一般帽子',
                    'short_name' => '一般帽子',
                    'price' => 560,
                ],
                [
                    'name' => '手袋',
                    'short_name' => '手袋',
                    'price' => 370,
                ],
                [
                    'name' => '手袋（デザイン・レース）',
                    'short_name' => '手袋（デザイン・レース）',
                    'price' => 420,
                ],
                [
                    'name' => 'マフラー',
                    'short_name' => 'マフラー',
                    'price' => 430,
                ],
            ]
        ];

        foreach ($list as $key => $arr) {
            $obj = Category::query()->create([
                'store_id' => 1,
                'name' => $key
            ]);

            foreach ($arr as $_key => $_arr) {
                Clothes::query()->create([
                    'store_id' => 1,
                    'category_id' => $obj->id,
                    'name' => $_arr['name'],
                    'short_name' => $_arr['short_name'],
                    'price' => $_arr['price'],
                ]);
            }
        }
    }
}
