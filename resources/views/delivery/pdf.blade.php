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
        body { font-family: migmix; line-height: 80%; margin: 15px 20px; }
        .bold-font { font-family: migmix-bold !important; }
        .number-font { font-family: Arial, Helvetica, sans-serif !important; }
        .text-end { text-align: right !important; }
        .text-center { text-align: center; }

        .doc-title { font-family: migmix-bold; font-size: 24px; text-align: center; margin-bottom: 5px; border-bottom: 1px solid #000; display: inline-block; padding: 0 20px 3px 20px; }
        .doc-title-wrap { text-align: center; margin-bottom: 8px; }
        .doc-meta-inner { width: 100%; }
        .customer-block { font-size: 18px; }
        .store-block { text-align: right; font-size: 13px; }

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
        .total-row td { font-size: 13px; border-top: 2px solid #000; }
    </style>
</head>
<body>
    <div class="doc-title-wrap">
        <span class="doc-title">納　品　書</span>
    </div>

    <table class="doc-meta-inner" style="border:none; margin-bottom:6px;">
        <tr>
            <td style="border:none; padding:0;">
                期間：<span class="number-font">{{ \Carbon\Carbon::parse($periodStart)->format('Y') }}</span>年<span class="number-font">{{ \Carbon\Carbon::parse($periodStart)->format('n') }}</span>月<span class="number-font">{{ \Carbon\Carbon::parse($periodStart)->format('j') }}</span>日
                〜 <span class="number-font">{{ \Carbon\Carbon::parse($periodEnd)->format('Y') }}</span>年<span class="number-font">{{ \Carbon\Carbon::parse($periodEnd)->format('n') }}</span>月<span class="number-font">{{ \Carbon\Carbon::parse($periodEnd)->format('j') }}</span>日
            </td>
            <td style="border:none; padding:0; text-align:right;">
                No. <span class="number-font bold-font">{{ $note->note_number }}</span>
            </td>
        </tr>
    </table>

    <table class="header-box" style="border:none; margin-bottom:8px;">
        <tr>
            <td style="border:none; padding:0;" class="customer-block bold-font">{{ $customer->name }} 御中</td>
            <td style="border:none; padding:0; text-align:right;" class="store-block">
                あさひ屋クリーニング
            </td>
        </tr>
    </table>

    <p style="font-size:11px; margin:4px 0 6px 0;">下記のとおり納品いたしました</p>

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
