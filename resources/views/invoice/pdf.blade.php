<html lang="ja">
    <head>
        <title>pdf output test</title>
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
                position: relative;
            }
            .number-font {
                font-family: Arial, Helvetica, sans-serif !important;
            }
            .text-center {
                text-align: center;
            }
            .text-end {
                text-align: right !important;
            }
            .text-start {
                text-align: left !important;
            }
            .bold-font {
                font-family: migmix-bold !important;
            }
            .number-height {
                line-height: 1.3;
            }
            .page_break {
                page-break-before: always;
            }


            .date {
                position: absolute;
                top: 0;
                right: 80px;
            }
            .count {
                position: absolute;
                top: 0;
                right: 0;
                font-size: 20px;
            }
            .title {
                width: 100%;
                text-align: center;
                position: relative;
            }
            .title-label {
                font-size: 24px;
                border-bottom: 1px solid #000000;
                display: inline-block;
                padding: 0 10px 3px 10px;
                margin-bottom: 5px;
            }
            .cutoff {
                width: 100%;
                text-align: center;
                position: relative;
                font-size: 18px;
            }
            .no {
                position: absolute;
                top: 23px;
                /* right: 0; */
                left: 529px;
                font-size: 16px;
                border-bottom: 1px solid #000000;
                padding: 0 3px 2px 3px;
            }
            .store_name {
                position:absolute;
                top: 55px;
                left: 400px;
            }
            .manager_name {
                position: absolute;
                top: 55px;
                right: 0;
            }
            .address {
                position: absolute;
                top: 75px;
                left: 400px;
            }
            .customer_name {
                position: absolute;
                top: 95px;
                left: 100px;
            }
            .tel {
                position: absolute;
                top: 95px;
                left: 400px;
            }
            .fax {
                position: absolute;
                top: 95px;
                right: 0;
            }
            .square-field {
                position: absolute;
                top: 120px;
                right: 0;
                width: 100px;
            }
            .square-right {
                width: 50px;
                height: 50px;
                border-top: 1px solid #000000;
                border-bottom: 1px solid #000000;
                border-left: 1px solid #000000;
                float: right;
            }
            .square-left {
                width: 50px;
                height: 50px;
                border: 1px solid #000000;
                float: right;
            }
            .total {
                width: 100%;
            }
            .total-left-table {
                position: absolute;
                top: 180px;
                left: 0;
                width: 75%;
            }
            .total-left-table th,td {
                width: 20%;
            }
            .total-right-table {
                position: absolute;
                top: 180px;
                right: 0;
                width: 15%;
            }
            .total td {
                line-height: 1.2;
                padding: 0 5px;
            }
            table {
                border-collapse: collapse;
                border: 2px solid #000000;
            }
            table th, td {
                border: 1px solid #000000;
                text-align: center;
            }
            table th {
                background-color: #e6e6e6;
            }
            .list {
                position: absolute;
                top: 240px;
                left: 0;
                width: 100%;
            }
            .order-list {
                width: 100%;
            }
            .order-date {
                width: 15%;
            }
            .order-no {
                width: 15%;
            }
            .order-detail {
                width: 100%;
            }
            .order-div {
                width: 10px;
            }
            .order-price {
                width: 15%;
            }
            .order-count {
                width: 7%;
            }
            .order-amount {
                width: 25%;
            }
            .order-payment {
                width: 25%;
            }
            .empty-column {
                height: 20px;
            }

        </style>
    </head>
    <body>
        @foreach ($invoices as $invoice)
        <div class="date number-font">
            <div class="date-label">({{ date('Y/m/d') }})</div>
        </div>
        <div class="count number-font">
            <div class="count-label">{{ $invoice['page_count'] }}/　{{ array_count_values(array_column($invoices, 'id'))[$invoice['id']] }}</div>
        </div>
        <div class="title">
            <div class="title-label bold-font">＊ ＊ 請求書 ＊ ＊</div>
        </div>

        <div class="cutoff">
            <div class="cutoff-label">
                <span class="number-font">{{ date('Y', strtotime($invoice['period_end'])) }}</span>年<span class="number-font">{{ date('m', strtotime($invoice['period_end'])) }}</span>月<span class="number-font">{{ date('d', strtotime($invoice['period_end'])) }}</span>日締
            </div>
        </div>

        <div class="no">
            <div class="no-label">請求No. <span class="number-font">{{ str_pad($invoice['id'], 5, 0, STR_PAD_LEFT) }}-{{ date('ymd', strtotime($invoice['period_end'])) }}</span></div>
        </div>

        <div class="store_name">{{ Auth::user()->name }}</div>

        <div class="manager_name">担当:{{ session()->get('manager_name') }}</div>

        <div class="address">〒<span class="number-font">000-0000</span>　{{ Auth::user()->address }}</div>

        <div class="customer_name bold-font">{{ $invoice['customer_name'] }}　様</div>

        <div class="tel">TEL <span class="number-font">{{ Auth::user()->phone_number }}</span></div>
        <div class="fax">FAX <span class="number-font">{{ Auth::user()->phone_number }}</span></div>

        <div class="square-field">
            <div class="square-left"></div>
            <div class="square-right"></div>
        </div>

        <div class="total">
            <table class="total-left-table">
                <thead>
                    <tr>
                        <th>前回残高</th>
                        <th>御入金金額</th>
                        <th></th>
                        <th>繰越金</th>
                        <th>今回売上額</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="number-font text-end">{{ number_format($invoice['carried_over_amount']) }}</td>
                        <td class="number-font text-end">0</td>
                        <td class="number-font text-end"></td>
                        <td class="number-font text-end">{{ number_format($invoice['carried_over_amount']) }}</td>
                        <td class="number-font text-end">{{ number_format($invoice['amount']) }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="total-right-table">
                <thead>
                    <tr>
                        <th>今回御請求額</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="number-font text-end">{{ number_format($invoice['amount'] + $invoice['carried_over_amount']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="list">
            <table class="order-list">
                <thead>
                    <tr>
                        <th class="order-date">年月日</th>
                        <th class="order-no">伝票No</th>
                        <th class="order-detail">タグ/商品名/色柄加工等</th>
                        <th class="order-div">区分</th>
                        <th class="order-price">単価</th>
                        <th class="order-count">数量</th>
                        <th class="order-amount">売上金額</th>
                        <th class="order-payment">入金額</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $line = 0;
                    @endphp
                    @foreach ($invoice['row'] as $row)
                    @if ($row['is_detail'])
                    <tr>
                        <td class="order-date number-font number-height">{{ date('y/m/d', strtotime($row['ordered_at'])) }}</td>
                        <td class="order-no text-center number-font number-height">{{ $row['order_id'] }}</td>
                        <td class="order-detail text-start"><span class="number-font">{{ $row['tag'] }}</span> <span>{{ $row['name'] }}</span></td>
                        <td class="order-div"></td>
                        <td class="order-price text-end number-font number-height">{{ number_format($row['price']) }}</td>
                        <td class="order-count text-end number-font number-height">1</td>
                        <td class="order-amount text-end number-font number-height">{{ number_format($row['price']) }}</td>
                        <td class="order-payment text-end number-font number-height"></td>
                    </tr>
                    @else
                    <tr style="border-bottom: 2px solid #000000;">
                        <td style="border-right: none;"></td>
                        <td style="border-right: none;border-left: none;"></td>
                        <td style="border-right: none;border-left: none;"></td>
                        <td style="border-right: none;border-left: none;"></td>
                        <td style="border-right: none;border-left: none;">{{ '<<合計>>' }}</td>
                        <td class="number-font text-end number-height" style="border-right: none;border-left: none;">{{ count($row['items']) }}</td>
                        <td class="number-font text-end number-height" style="border-right: none;border-left: none;">{{ number_format($row['amount']) }}</td>
                        <td class="number-font" style="border-left: none;"></td>
                    </tr>
                    @endif
                    @php
                        $line++;
                    @endphp
                    @endforeach
                    @if ($line < 30)
                    @for ($i = $line; $i <= 30; $i++)
                        <tr>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                            <td class="empty-column"></td>
                        </tr>
                    @endfor
                    @endif
                </tbody>
            </table>
        </div>
        @if ($invoice !== end($invoices))
        <div class="page_break"></div>
        @endif
        @endforeach
    </body>
</html>