<html lang="ja">
    <head>
        <title>日報</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            @font-face{
                font-family: migmix;
                font-style: normal;
                font-weight: normal;
                src:url('{{ storage_path('fonts/migmix-2m-regular.ttf')}}');
            }
            @font-face{
                font-family: migmix-bold;
                font-style: normal;
                font-weight: normal;
                src:url('{{ storage_path('fonts/migmix-2m-bold.ttf')}}');
            }

            body {
                font-family: migmix;
                /* font-family: migmix, serif !important; */
                /* font-family: migmix-bold, serif !important; */
                /* https://blog.websandbag.com/entry/2020/11/23/221039 */
                line-height: 80%;
                page-break-inside: avoid;
            }
            .number-font {
                font-family: Arial, Helvetica, sans-serif !important;
            }
            .text-center {
                text-align: center;
            }
            .nowrap {
                white-space: nowrap;
            }
            .bold-font {
                font-family: migmix-bold !important;
            }


            .title {
                width: 100%;
                text-align: center;
                position: relative;
            }
            .title-label {
                font-family: migmix-bold !important;
                font-size: 28px;
            }
            .no {
                font-size: 20px;
                width: 20%;
                position: absolute;
                right: 0px;
                top: 0px;
                text-align: right;
            }
            .no-area {
                border-bottom: 1px solid #000000;
                margin-right: 0;
                width: 100%;
                text-align: left;
            }
            .no-area span {
                padding-left: 40px;
            }

            .date {
                width: 130px;
                margin-left: 50px;
                margin-top: -30px;

                font-size: 24px;
                text-align: center;
            }
            .date-label {
                border-bottom: 1px solid #000000;
                line-height: 30px;
            }

            .income {
                width: 60%;
                margin: 0 auto;
                margin-top: 10px;
                height: 20px;
            }
            .income-price {
                display: inline-block;
                border: 1px solid #000000;
                text-align: center;
                padding-right: 10px;
                width: 90px;
                line-height: 1.1;
                margin-bottom: -5px
            }
            .income-label {
                margin-left: 20px;
            }

            .list {
                margin-top: 30px;
            }
            .table {
                border-collapse: collapse;
                font-family: migmix;
                width: 100%
            }
            .short-label {
                transform: scale(0.7, 1);
            }
            .tag-label {

            }
            .item-label {
                max-width: 50px;
                /* max-width: 140px; */
                white-space: nowrap;
            }
            .price-label {

            }
            .no-label {

            }
            .name-label {
                max-width: 50px;
                white-space: nowrap;
            }
            .paid-label {
                
            }
            .summary-label {
                width: 30%;
            }
            .page_break {
                page-break-before: always;
            }
            .empty-column {
                height: 21px;
            }
            .bold-border {
                border-top: 2px solid #000000 ;
            }
        </style>
    </head>
    <body>
        @for($c = 0; $c <= count($daily_orders) - 1; $c += 35)
        <div class="no">
            <div class="no-area">
                No.<span class="number-font">{{ $c / 35 + 1 }}</span>
            </div>
        </div>
        <div class="title">
            <div class="title-label">【　日　報　】</div>
        </div>

        <div class="date">
            <p class="date-label"><span class="number-font">{{ date('m', strtotime($date)) }}</span>月<span class="number-font">{{ date('d', strtotime($date)) }}</span>日</span>
        </div>

        <div class="income">
            <span>入金額</span>
            <span class="income-label">日計</span>
            <div class="income-price">
                <span>￥<span class="number-font">{{ number_format($daily_sum) }}</span></span>
            </div>
            <span class="income-label">累計</span>
            <div class="income-price">
                <span>￥<span class="number-font">{{ number_format($monthly_sum) }}</span></span>
            </div>
        </div>

        <div class="list">
            {{-- w:185.74mm --}}
            <table class="table" rules="all">
                <thead>
                    <tr class="bold-font text-center">
                        <td class="tag-label">タグNo</td>
                        <td class="item-label">品名</td>
                        <td class="price-label">値段</td>
                        <td class="no-label">会員No</td>
                        <td class="name-label">氏名</td>
                        <td class="paid-label">未収</td>
                        <td class="summary-label">摘要</td>
                        <td class="arrive-label">到着日</td>
                        <td class="handed-label">渡し日</td>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $customer_id = '';
                        $prev_clothes = '';
                        $prev_order = '';
                        $disp_clothes = true;
                        $disp_customer = true;
                    @endphp
                    @for ($i = $c; $i == $c || $i % 35 != 0; $i++)
                        @if ($i <= count($daily_orders) - 1)
                        @php
                            if ($prev_order === $daily_orders[$i]['order_id']) {
                                $disp_customer = false;
                                if ($prev_clothes === $daily_orders[$i]['clothes_id'] || $daily_orders[$i]['clothes_id'] === 999) {
                                    $disp_clothes = false;
                                } else {
                                    $disp_clothes = true;
                                }
                            } else {
                                $disp_customer = true;
                                $disp_clothes = true;
                            }
                        @endphp
                        <tr class="{{ $i % 35 !== 0 && $disp_customer ? 'bold-border': '' }}">
                            <td class="number-font text-center">{{ $daily_orders[$i]['tag'] }}</td>
                            @if ($disp_clothes)
                                <td class=""><span class="short-label nowrap">{{ $daily_orders[$i]['name'] }}</span></td>
                            @else
                                <td class="empty-column"></td>
                            @endif
                            <td class="number-font text-center">{{ $daily_orders[$i]['price'] }}</td>
                            <td class="number-font text-center">{{ $daily_orders[$i]['customer_id'] }}</td>
                            @if ($disp_customer)
                                <td style="width: 20%;"><span class="short-label nowrap">{{ $daily_orders[$i]['customer_name'] }}</span></td>
                            @else
                                <td class="empty-column"></td>
                            @endif
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        @php
                            $prev_clothes = $daily_orders[$i]['clothes_id'];
                            $prev_order = $daily_orders[$i]['order_id'];
                        @endphp
                        @else
                        <tr>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                        </tr>
                        @endif
                    @endfor
                </tbody>
            </table>
        </div>
        @if ($i <= count($daily_orders) - 1)
        <div class="page_break"></div>
        @endif
        @endfor
    </body>
</html>