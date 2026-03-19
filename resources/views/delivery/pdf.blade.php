{{-- resources/views/delivery/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @font-face {
            font-family: migmix;
            src: url('{{ storage_path('fonts/migmix-2m-regular.ttf') }}');
        }
        @font-face {
            font-family: migmix-bold;
            src: url('{{ storage_path('fonts/migmix-2m-bold.ttf') }}');
        }
        body { font-family: migmix; font-size: 11px; margin: 15px 20px; }
        .bold { font-family: migmix-bold; }
        .num { font-family: Arial, sans-serif; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .doc-title { font-family: migmix-bold; font-size: 18px; text-align: center; margin-bottom: 4px; }
        .doc-meta { margin-bottom: 8px; font-size: 11px; }
        .doc-meta-inner { width: 100%; }
        .header-box { width: 100%; margin-bottom: 8px; }
        .customer-block { font-size: 13px; }
        .store-block { text-align: right; font-size: 11px; }

        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #555; padding: 3px 6px; }
        th { background-color: #e0e0e0; font-family: migmix-bold; text-align: center; }
        .dept-header td { background-color: #f5f5f5; font-family: migmix-bold; }
        .subtotal-row td { border-top: 2px solid #333; }
        .col-no { width: 5%; text-align: center; }
        .col-name { width: 35%; }
        .col-qty { width: 10%; text-align: right; }
        .col-price { width: 12%; text-align: right; }
        .col-amount { width: 15%; text-align: right; }
        .col-note { width: 23%; }

        .tax-section { margin-top: 10px; }
        .tax-table { width: 50%; margin-left: 50%; border-collapse: collapse; }
        .tax-table td { border: 1px solid #555; padding: 3px 8px; }
        .total-row td { font-family: migmix-bold; font-size: 13px; }
    </style>
</head>
<body>
    <div class="doc-title">納　品　書（控）</div>

    <table class="doc-meta-inner" style="border:none; margin-bottom:8px;">
        <tr>
            <td style="border:none; padding:0;">
                期間：<span class="num">{{ \Carbon\Carbon::parse($periodStart)->format('Y') }}</span>年<span class="num">{{ \Carbon\Carbon::parse($periodStart)->format('n') }}</span>月<span class="num">{{ \Carbon\Carbon::parse($periodStart)->format('j') }}</span>日
                〜 <span class="num">{{ \Carbon\Carbon::parse($periodEnd)->format('Y') }}</span>年<span class="num">{{ \Carbon\Carbon::parse($periodEnd)->format('n') }}</span>月<span class="num">{{ \Carbon\Carbon::parse($periodEnd)->format('j') }}</span>日
            </td>
            <td style="border:none; padding:0; text-align:right;">
                No. <span class="num bold">{{ $note->note_number }}</span>
            </td>
        </tr>
    </table>

    <table class="header-box" style="border:none; margin-bottom:8px;">
        <tr>
            <td style="border:none; padding:0;" class="customer-block bold">{{ $customer->name }} 御中</td>
            <td style="border:none; padding:0; text-align:right;" class="store-block">
                あさひ屋クリーニング<br>
                @if($customer->registration_number)
                登録番号: <span class="num">{{ $customer->registration_number }}</span>
                @endif
            </td>
        </tr>
    </table>

    <p style="font-size:11px; margin:4px 0 6px 0;">下記のとおり納品いたしました</p>

    <table>
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-name">品　名</th>
                <th class="col-qty">数量</th>
                <th class="col-price">単価</th>
                <th class="col-amount">金額(税抜)</th>
                <th class="col-note">摘要</th>
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
                <td class="col-no num">{{ $rowNo++ }}</td>
                <td class="col-name">　{{ $product['name'] }}</td>
                <td class="col-qty num">{{ number_format($product['quantity']) }}</td>
                <td class="col-price num">{{ number_format($product['unit_price']) }}</td>
                <td class="col-amount num">{{ number_format($product['amount']) }}</td>
                <td class="col-note"></td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td class="col-no"></td>
                <td class="col-name bold" style="text-align:right">小　計</td>
                <td class="col-qty"></td>
                <td class="col-price"></td>
                <td class="col-amount num bold">{{ number_format($dept['subtotal']) }}</td>
                <td class="col-note"></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="tax-section">
        <table class="tax-table">
            @foreach($taxGroups as $group)
            <tr>
                <td>税率 <span class="num">{{ intval($group['rate'] * 100) }}</span>% 対象</td>
                <td style="text-align:right;" class="num">{{ number_format($group['subtotal_excl']) }}</td>
                <td>消費税</td>
                <td style="text-align:right;" class="num">{{ number_format($group['tax']) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align:right;">税込合計金額</td>
                <td colspan="2" style="text-align:right;" class="num">
                    ¥{{ number_format(array_sum(array_column($taxGroups, 'subtotal_incl'))) }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
