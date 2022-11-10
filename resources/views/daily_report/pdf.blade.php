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
        </style>
    </head>
    <body>
        <div class="no">
            <div class="no-area">
                No.<span class="number-font">1</span>
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
            <table class="table" rules="all">
                <thead>
                    <tr>
                        <th>タグNo.</th>
                        <th>品名</th>
                        <th>値段</th>
                        <th>会員No.</th>
                        <th>氏名</th>
                        <th><span class="short-label">未収・定</span></th>
                        <th>摘要</th>
                        <th>到着日</th>
                        <th>渡し日</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $customer_id = '';
                    @endphp
                    @foreach ($daily_orders as $order)
                        {{-- @php
                        $customer_id = 
                        @endphp --}}
                        @foreach ($order['items'] as $item)
                        <tr>
                            <td class="number-font text-center">{{ $item['tag'] }}</td>
                            <td class="item-label"><span class="short-label">{{ $item['name'] }}</span></td>
                            <td class="number-font text-center">{{ $item['price'] }}</td>
                            <td class="text-center">{{ $order['customer_id'] }}</td>
                            <td class="name-label"><span class="short-label">{{ $order['name'] }}</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </body>
</html>