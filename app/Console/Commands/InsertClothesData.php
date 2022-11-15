<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Goodby\CSV\Import\Standard\LexerConfig;
use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\Interpreter;

use App\Models\Clothes;

class InsertClothesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InsertClothesData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 抽出カラム（商品）

        // 商品コード: id(column1, A)
        // カナ商品名: name_kana(column2, B)
        // 漢字商品名: name(column5, E)
        // 任意２分類コード: category_id(column6, )
        // タグ枚数: tag_count(column9, )
        // 単価１: price(column10, )
        // 作成日: created_at(column30, )
        // 更新日: updated_at(column31, )

        // （要考慮）
        // 任意１分類コード: ????(column6, F)

        $path = base_path('import/clothes.csv');

        $config = new LexerConfig();
        $interpreter = new Interpreter();
        $lexer = new Lexer($config);

        //CharsetをUTF-8に変換、CSVのヘッダー行を無視
        $config->setToCharset("UTF-8");
        $config->setFromCharset("UTF-8");
        $config->setIgnoreHeaderLine(true);

        $dataList = [];

        // 新規Observerとして、$dataList配列に値を代入
        $interpreter->unstrict();
        $interpreter->addObserver(function (array $row) use (&$dataList){
            // 各列のデータを取得
            $dataList[] = $row;
        });

        // CSVデータをパース
        $lexer->parse($path, $interpreter);

        // 登録処理
        foreach($dataList as $row){
            if (empty($row[5])) continue;
            Clothes::create([
                'store_id' => 1,
                'id' => $row[0],
                'name_kana' => $row[1],
                'name' => $row[2],
                'category_id' => $row[5],
                'tag_count' => $row[8],
                'price' => $row[9],
                'created_at' => date('Y-m-d 00:00:00', strtotime($row[29])),
                'updated_at' => date('Y-m-d 00:00:00', strtotime($row[30])),
            ]);
        }
    }
}
