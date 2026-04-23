{{-- resources/views/delivery/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @font-face {
            font-family: migmix;
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/migmix-2m-regular.ttf') }}');
        }
        @font-face {
            font-family: migmix-bold;
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/migmix-2m-bold.ttf') }}');
        }
        body { font-family: migmix; line-height: 80%; page-break-inside: avoid; position: relative; margin: 15px 20px; }
        .bold-font { font-family: migmix-bold !important; }
        .number-font { font-family: Arial, Helvetica, sans-serif !important; }
        .text-end { text-align: right !important; }
        .text-start { text-align: left !important; }
        .text-center { text-align: center; }
        .normal-font { font-family: migmix !important; }

        .date { position: absolute; top: 0; right: 0; }
        .title { width: 100%; text-align: center; position: relative; }
        .title-label { font-size: 24px; border-bottom: 1px solid #000000; display: inline-block; padding: 0 10px 3px 10px; margin-bottom: 5px; }
        .cutoff { width: 100%; text-align: center; position: relative; font-size: 18px; }
        .no { position: absolute; top: 30px; right: 0; font-size: 16px; border-bottom: 1px solid #000000; padding: 0 3px 2px 3px; }
        .store_name { position: absolute; top: 58px; left: 360px; }
        .manager_name { position: absolute; top: 58px; right: 0; }
        .address { position: absolute; top: 76px; left: 360px; }
        .customer_name { white-space: pre-wrap; position: absolute; top: 90px; left: 0; font-size: 18px; }
        .invoice_num { position: absolute; top: 130px; left: 360px; }
        .tel { position: absolute; top: 94px; left: 360px; }
        .fax { position: absolute; top: 94px; right: 0; }
        .square-field { position: absolute; top: 118px; right: 0; width: 105px; }
        .square-right { width: 50px; height: 50px; border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; float: right; }
        .square-left { width: 50px; height: 50px; border: 1px solid #000000; float: right; }

        .header-area { position: relative; height: 190px; }

        .period-info { margin-bottom: 6px; font-size: 13px; }

        table { border-collapse: collapse; width: 100%; border: 2px solid #000; }
        td { border: 1px solid #000; padding: 3px 6px; }
        .col-header { background-color: #e6e6e6; text-align: center; line-height: 1 !important; }
        .dept-header td { background-color: #f5f5f5; }
        .subtotal-row td { border-top: 2px solid #000; }
        .col-no { width: 5%; text-align: center; }
        .col-name { width: 35%; }
        .col-qty { width: 10%; text-align: right; }
        .col-price { width: 12%; text-align: right; }
        .col-amount { width: 15%; text-align: right; }
        .col-note { width: 23%; }

        .tax-section { margin-top: 10px; }
        .tax-table { width: 50%; margin-left: 50%; border-collapse: collapse; border: 2px solid #000; }
        .tax-table td { border: 1px solid #000; padding: 3px 8px; }
        .total-row td { border-top: 2px solid #000; }
    </style>
</head>
<body>
    <div class="header-area">
        <div class="date number-font">
            <div class="date-label"> </div>
        </div>
        <div class="title">
            <div class="title-label bold-font">＊ ＊ 請求書 ＊ ＊</div>
        </div>

        <div class="cutoff">
            <div class="cutoff-label">
                <span class="number-font">{{ \Carbon\Carbon::parse($cutoffDate)->format('Y') }}</span>年<span class="number-font">{{ \Carbon\Carbon::parse($cutoffDate)->format('n') }}</span>月<span class="number-font">{{ \Carbon\Carbon::parse($cutoffDate)->format('j') }}</span>日締
            </div>
        </div>


        <div class="store_name">{{ Auth::user()->name }}</div>

        <div class="manager_name"></div>

        <div class="address">〒<span class="number-font">{{ Auth::user()->postal_code }}</span>　{{ Auth::user()->address }}</div>

        <div class="customer_name bold-font">{{ $customer->name }}　様</div>

        <div class="invoice_num">登録番号:<span class="number-font">T8810957628818</span></div>

        <div class="tel">TEL <span class="number-font">0973-{{ Auth::user()->phone_number }}</span></div>

        <div class="square-field">
            <div class="square-left"></div>
            <div class="square-right"></div>
        </div>
    </div>


    <table>
        <thead>
            <tr>
                <td class="col-header col-no">No</td>
                <td class="col-header col-name">品　名</td>
                <td class="col-header col-qty">数量</td>
                <td class="col-header col-price">単価</td>
                <td class="col-header col-amount">金額(税抜)</td>
                <td class="col-header col-note">摘要</td>
            </tr>
        </thead>
        <tbody>
        @php $rowNo = 1; @endphp
        @foreach($entries as $dept)
            <tr class="dept-header">
                <td></td>
                <td colspan="5">▼ {{ $dept['name'] }}</td>
            </tr>
            @foreach($dept['products'] as $product)
            <tr>
                <td class="col-no number-font">{{ $rowNo++ }}</td>
                <td class="col-name">　{{ $product['name'] }}</td>
                <td class="col-qty number-font text-end">{{ number_format($product['quantity']) }}</td>
                <td class="col-price number-font text-end">{{ number_format($product['unit_price']) }}</td>
                <td class="col-amount number-font text-end">{{ number_format($product['amount']) }}</td>
                <td class="col-note"></td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td class="col-no"></td>
                <td class="col-name text-end">小　計</td>
                <td class="col-qty"></td>
                <td class="col-price"></td>
                <td class="col-amount number-font text-end">{{ number_format($dept['subtotal']) }}</td>
                <td class="col-note"></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="tax-section">
        <table class="tax-table">
            <thead>
                <tr>
                    <td class="col-header">税率区分</td>
                    <td class="col-header">税抜金額</td>
                    <td class="col-header">消費税</td>
                </tr>
            </thead>
            <tbody>
                @foreach($taxGroups as $group)
                <tr>
                    <td><span class="number-font">{{ intval($group['rate'] * 100) }}</span>% 対象</td>
                    <td class="number-font text-end">{{ number_format($group['subtotal_excl']) }}</td>
                    <td class="number-font text-end">{{ number_format($group['tax']) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" style="text-align:right;">税込合計金額</td>
                    <td class="number-font text-end">
                        ¥{{ number_format(array_sum(array_column($taxGroups, 'subtotal_incl'))) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
