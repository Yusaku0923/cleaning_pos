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
            'カスタム' => [
                [
                    'name' => '手入力',
                    'name_kana' => '手入力',
                    'price' => 9999
                ],
            ],
            'シャツ' =>[
                [
                    'name' => 'Yシャツ（立体仕上げ）',
                    'name_kana' => 'Yｼｬﾂ',
                    'price' => 170
                ],
                [
                    'name' => 'Yシャツ（色柄立体仕上げ）',
                    'name_kana' => 'Yｼｬﾂ（色柄立体仕上げ）',
                    'price' => 230
                ],
                [
                    'name' => 'Yシャツ（たたみ仕上げ）',
                    'name_kana' => 'Yｼｬﾂ（たたみ仕上げ）',
                    'price' => 190
                ],
                [
                    'name' => 'デザインYシャツ',
                    'name_kana' => 'デザインYシャツ',
                    'price' => 270
                ],
                [
                    'name' => 'Yシャツ（手仕上げ）',
                    'name_kana' => 'Yシャツ（手仕上げ）',
                    'price' => 360
                ],
                [
                    'name' => 'シャツ（立体仕上げ）',
                    'name_kana' => 'シャツ（立体仕上げ）',
                    'price' => 430
                ],
                [
                    'name' => 'ブラウス',
                    'name_kana' => 'ﾌﾞﾗｳｽ',
                    'price' => 430
                ],
            ],
            '紳士服' => [
                [
                    'name' => '紳士上着',
                    'name_kana' => 'ｼﾝｼｳﾜｷﾞ',
                    'price' => 550,
                ],
                [
                    'name' => 'ベスト',
                    'name_kana' => 'ﾍﾞｽﾄ',
                    'price' => 430,
                ],
                [
                    'name' => 'ズボン',
                    'name_kana' => 'ｽﾞﾎﾞﾝ',
                    'price' => 430,
                ],
                [
                    'name' => 'ネクタイ',
                    'name_kana' => 'ﾈｸﾀｲ',
                    'price' => 380,
                ],
            ],
            '婦人服' => [
                [
                    'name' => '婦人上着',
                    'name_kana' => 'ﾌｼﾞﾝｳﾜｷﾞ',
                    'price' => 550,
                ],
                [
                    'name' => 'パンツ',
                    'name_kana' => 'ﾊﾟﾝﾂ',
                    'price' => 430,
                ],
                [
                    'name' => 'スカート',
                    'name_kana' => 'スカート',
                    'price' => 430,
                ],
                [
                    'name' => 'ワンピース',
                    'name_kana' => 'ワンピース',
                    'price' => 770,
                ],
            ],
            'ジャンパー･コート' => [
                [
                    'name' => 'ジャンパー',
                    'name_kana' => 'ジャンパー',
                    'price' => 710,
                ],
                [
                    'name' => 'ハーフコート',
                    'name_kana' => 'ハーフコート',
                    'price' => 830,
                ],
                [
                    'name' => 'コート',
                    'name_kana' => 'コート',
                    'price' => 930,
                ],
                [
                    'name' => 'ダウンジャンパー',
                    'name_kana' => 'ダウンジャンパー',
                    'price' => 1800,
                ],
                [
                    'name' => 'ダウンハーフコート',
                    'name_kana' => 'ダウンハーフコート',
                    'price' => 1900,
                ],
            ],
            'セーター' => [
                [
                    'name' => 'セーター',
                    'name_kana' => 'セーター',
                    'price' => 430
                ],
                [
                    'name' => 'カーディガン',
                    'name_kana' => 'カーディガン',
                    'price' => 430
                ],
            ],
            '制服' => [
                [
                    'name' => '学生服（上）',
                    'name_kana' => '学生服（上）',
                    'price' => 550,
                ],
                [
                    'name' => 'セーラー服（上）',
                    'name_kana' => 'セーラー服（上）',
                    'price' => 550,
                ],
                [
                    'name' => '学生ズボン',
                    'name_kana' => '学生ズボン',
                    'price' => 430,
                ],
                [
                    'name' => '学生ヒダスカート',
                    'name_kana' => '学生ヒダスカート',
                    'price' => 480,
                ],
            ],
            '幼児（サイズ 130ｃｍまで）' => [
                [
                    'name' => 'シャツ',
                    'name_kana' => 'シャツ',
                    'price' => 380,
                ],
                [
                    'name' => 'セーター',
                    'name_kana' => 'セーター',
                    'price' => 380,
                ],
                [
                    'name' => '上着',
                    'name_kana' => '上着',
                    'price' => 380,
                ],
                [
                    'name' => 'ズボン',
                    'name_kana' => 'ズボン',
                    'price' => 380,
                ],
                [
                    'name' => 'スカート',
                    'name_kana' => 'スカート',
                    'price' => 380,
                ],
                [
                    'name' => 'ジャンパー',
                    'name_kana' => 'ジャンパー',
                    'price' => 380,
                ],
                [
                    'name' => 'コート',
                    'name_kana' => 'コート',
                    'price' => 380,
                ],
            ],
            '白物・その他' => [
                [
                    'name' => '白衣（長）',
                    'name_kana' => '白衣（長）',
                    'price' => 430,
                ],
                [
                    'name' => '白衣ワンピース',
                    'name_kana' => '白衣ワンピース',
                    'price' => 480,
                ],
                [
                    'name' => 'ハッピ',
                    'name_kana' => 'ハッピ',
                    'price' => 450,
                ],
                [
                    'name' => 'ゆかた（機械仕上げ）',
                    'name_kana' => 'ゆかた（機械仕上げ）',
                    'price' => 1200,
                ],
                [
                    'name' => 'ゆかた（手仕上げ）',
                    'name_kana' => 'ゆかた（手仕上げ）',
                    'price' => 2000,
                ],
            ],
            '小物' => [
                [
                    'name' => '野球帽',
                    'name_kana' => '野球帽',
                    'price' => 420,
                ],
                [
                    'name' => '一般帽子',
                    'name_kana' => '一般帽子',
                    'price' => 560,
                ],
                [
                    'name' => '手袋',
                    'name_kana' => '手袋',
                    'price' => 370,
                ],
                [
                    'name' => '手袋（デザイン・レース）',
                    'name_kana' => '手袋（デザイン・レース）',
                    'price' => 420,
                ],
                [
                    'name' => 'マフラー',
                    'name_kana' => 'マフラー',
                    'price' => 430,
                ],
            ]
        ];

        // if (config('app.env') !== 'production') {
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
                        'name_kana' => $_arr['name_kana'],
                        'price' => $_arr['price'],
                    ]);
                }
            }
        //

        for ($i = 1; $i <= 26; $i++) {
            Category::create([
                'store_id' => 1,
                'name' => 'カテゴリ' . $i
            ]);
        }

        // system 
        $system = [
            'システム' => [
                [
                    'name' => '〃',
                    'name_kana' => '',
                    'price' => 0,
                ]
            ]
        ];

        foreach ($system as $key => $arr) {
            Category::query()->create([
                'id' => 999,
                'store_id' => 1,
                'name' => $key
            ]);

            foreach ($arr as $_arr) {
                Clothes::query()->create([
                    'id' => 999,
                    'store_id' => 1,
                    'category_id' =>999,
                    'name' => $_arr['name'],
                    'name_kana' => $_arr['name_kana'],
                    'price' => $_arr['price'],
                ]);
            }
        }
    }
}
