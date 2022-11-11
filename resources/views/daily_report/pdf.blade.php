<html lang="ja">
    <head>
        <title>pdf output test</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>

            body {
                font-family: migmix, serif;
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


            .title {
                width: 100%;
                text-align: center;
                position: relative;
            }
            .title-label {
                font-family: migmix-bold, serif !important;
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
                width: 100%
            }
            .short-label {
                transform: scale(0.7, 1);
            }
            .item-label {
                max-width: 50px;
                /* max-width: 140px; */
                white-space: nowrap;
            }
            .name-label {
                max-width: 50px;
                white-space: nowrap;
            }
            .page_break {
                page-break-before: always;
            }
            .empty-column {
                height: 21px;
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
                    <tr>
                        <th>タグNo.</th>
                        <th>品名</th>
                        <th>値段</th>
                        <th>会員No.</th>
                        <th>氏名</th>
                        <th>未収・定</th>
                        <th>摘要</th>
                        <th>到着日</th>
                        <th>渡し日</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $customer_id = '';
                    @endphp
                    @for ($i = $c; $i == $c || $i % 35 != 0; $i++)
                        @if ($i <= count($daily_orders) - 1)
                        <tr>
                            <td class="number-font text-center">{{ $daily_orders[$i]['tag'] }}</td>
                            <td class="item-label"><span class="short-label">{{ $daily_orders[$i]['name'] }}</span></td>
                            <td class="number-font text-center">{{ $daily_orders[$i]['price'] }}</td>
                            <td class="text-center">{{ $daily_orders[$i]['customer_id'] }}</td>
                            <td class="name-label"><span class="short-label">{{ $daily_orders[$i]['customer_name'] }}</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
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