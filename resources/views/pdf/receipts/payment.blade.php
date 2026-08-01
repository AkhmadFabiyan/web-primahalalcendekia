<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tanda Terima Pembayaran - {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .title { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin-bottom: 30px; }
        .row { margin-bottom: 10px; }
        .label { display: inline-block; width: 200px; font-weight: bold; }
        .footer { margin-top: 50px; font-size: 10px; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div style="text-align: right;">
        <h2>{{ $snapshot['company_profile']['company_name'] ?? 'PHC' }}</h2>
    </div>

    <div class="title">TANDA TERIMA PEMBAYARAN</div>

    <div class="row"><span class="label">No. Tanda Terima</span>: {{ $receipt->receipt_number }}</div>
    <div class="row"><span class="label">Tanggal Diterima</span>: {{ $snapshot['issued_at'] ? \Carbon\Carbon::parse($snapshot['issued_at'])->format('d F Y') : '-' }}</div>
    <div class="row"><span class="label">Telah terima dari</span>: {{ $receipt->invoice->project->client->name ?? '-' }}</div>
    <div class="row"><span class="label">Uang Sejumlah</span>: <strong>Rp {{ number_format($receipt->amount, 0, ',', '.') }}</strong></div>
    <div class="row"><span class="label">Untuk Pembayaran</span>: Pembayaran atas Invoice No. {{ $receipt->invoice->invoice_number }}</div>

    <div style="margin-top: 50px; text-align: right; padding-right: 50px;">
        Penerima,<br><br><br><br>
        <strong>{{ $receipt->issuer->name ?? 'Finance' }}</strong>
    </div>

    <div class="footer">
        {{ $snapshot['footer_note'] ?? '' }}
    </div>
</body>
</html>
