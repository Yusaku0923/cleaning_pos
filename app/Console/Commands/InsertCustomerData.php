<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Goodby\CSV\Import\Standard\LexerConfig;
use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\Interpreter;

use App\Models\Customer;

class InsertCustomerData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InsertCustomerData';

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
        // 抽出カラム（顧客）

        // 顧客コード: id(column1, A)
        // カナ氏名: name_kana(column4, D)
        // 漢字氏名: name(column5, E)
        // 電話番号: phone_number(column7, F)
        // 住所: address(column9~11, G~I)
        // 性別コード: sex(column15, )
        // 作成日: created_at(column25, )
        // 更新日: updated_at(column26, )
        // 外交担当コード: manager_id(column38, )
        // 請求締日: cutoff_date(column41, )
        // 請求入金管理区分: ???(column45, )

        // （要考慮）
        // 来店回数: number_of_visits(column29, )
        // 累計売上金額: total_sales(column31, )
        // 累計点数: total_points(column32, )

        $path = base_path('import/customer.csv');

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
            switch ($row[37]) {
                case 0:
                    $manager_id = 1;
                    break;
                case 11:
                    $manager_id = 2;
                    break;
                case 12:
                    $manager_id = 3;
                    break;
                case 13:
                    $manager_id = 4;
                    break;

            }
            Customer::create([
                'id' => $row[0],
                'name_kana' => $row[3],
                'name' => $row[4],
                'phone_number' => $row[6],
                // 'address' => $row[8] . $row[9] . $row[10],
                'sex' => $row[14],
                'created_at' => date('Y-m-d 00:00:00', strtotime($row[24])),
                'updated_at' => date('Y-m-d 00:00:00', strtotime($row[25])),
                'manager_id' => $manager_id,
                'cutoff_date' => $row[40],
                'is_invoice' => $row[44],
                'total_sales' => $row[29],
                'number_of_visits' => $row[28],
            ]);
        }
    }
}
