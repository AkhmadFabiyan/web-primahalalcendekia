<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kwitansi Lunas - {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .title { text-align: center; font-size: 24px; font-weight: bold; text-decoration: underline; margin-bottom: 30px; }
        .row { margin-bottom: 10px; }
        .label { display: inline-block; width: 200px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; }
        .footer { margin-top: 50px; font-size: 10px; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
        .stamp { color: red; font-size: 40px; font-weight: bold; border: 4px solid red; display: inline-block; padding: 10px; transform: rotate(-15deg); margin-top: 20px; }
    </style>
</head>
<body>
    <div style="text-align: right;">
        <h2>{{ $snapshot['company_profile']['company_name'] ?? 'PHC' }}</h2>
    </div>

    <div class="title">KWITANSI LUNAS</div>

    <div class="row"><span class="label">No. Kwitansi</span>: {{ $receipt->receipt_number }}</div>
    <div class="row"><span class="label">Tanggal Terbit</span>: {{ $snapshot['issued_at'] ? \Carbon\Carbon::parse($snapshot['issued_at'])->format('d F Y') : '-' }}</div>
    <div class="row"><span class="label">Telah terima dari</span>: {{ $receipt->invoice->project->client->name ?? '-' }}</div>
    <div class="row"><span class="label">Uang Sejumlah</span>: <strong>Rp {{ number_format($receipt->amount, 0, ',', '.') }}</strong></div>
    <div class="row"><span class="label">Untuk Pembayaran Lunas</span>: Seluruh tagihan pada Invoice No. {{ $receipt->invoice->invoice_number }}</div>

    <div style="text-align: center; margin: 30px 0;">
        <div class="stamp">LUNAS</div>
    </div>

    <h4>Rincian Pembayaran:</h4>
    <table>
        <thead>
            <tr>
                <th>Tanggal Bayar</th>
                <th>Referensi</th>
                <th>Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($snapshot['payments'] ?? [] as $pay)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($pay['payment_date'])->format('d M Y') }}</td>
                    <td>{{ $pay['reference_number'] ?? '-' }}</td>
                    <td>Rp {{ number_format($pay['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ $snapshot['footer_note'] ?? '' }}
    </div>
</body>
</html>
