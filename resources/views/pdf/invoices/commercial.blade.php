<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { display: table; width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .company-info { display: table-cell; vertical-align: top; width: 50%; }
        .invoice-info { display: table-cell; vertical-align: top; width: 50%; text-align: right; }
        .title { font-size: 24px; font-weight: bold; margin-bottom: 5px; color: #2563eb; }
        .watermark { position: fixed; top: 30%; left: 10%; font-size: 80px; color: rgba(255,0,0,0.1); transform: rotate(-45deg); z-index: -1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8fafc; font-weight: bold; }
        .totals { width: 40%; float: right; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .amount { text-align: right; font-weight: bold; }
        .footer { clear: both; margin-top: 50px; font-size: 10px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>

    @if($invoice->status === \App\Modules\Payments\Enums\InvoiceStatus::DRAFT)
        <div class="watermark">DRAFT</div>
    @elseif($invoice->status === \App\Modules\Payments\Enums\InvoiceStatus::CANCELLED)
        <div class="watermark">DIBATALKAN</div>
    @endif

    <div class="header">
        <div class="company-info">
            <h2>{{ $snapshot['company_profile']['company_name'] ?? 'PHC' }}</h2>
            <p>
                {{ $snapshot['company_profile']['address'] ?? '' }}<br>
                Telp: {{ $snapshot['company_profile']['phone'] ?? '' }}<br>
                Email: {{ $snapshot['company_profile']['email'] ?? '' }}
            </p>
        </div>
        <div class="invoice-info">
            <div class="title">INVOICE</div>
            <p>
                <strong>Nomor:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Tanggal Terbit:</strong> {{ $snapshot['issued_at'] ? \Carbon\Carbon::parse($snapshot['issued_at'])->format('d M Y') : '-' }}<br>
                <strong>Jatuh Tempo:</strong> {{ $snapshot['due_date'] ? \Carbon\Carbon::parse($snapshot['due_date'])->format('d M Y') : '-' }}<br>
                <strong>ID Klien:</strong> {{ $invoice->project->client->business_id ?? '-' }}<br>
                <strong>Klien:</strong> {{ $invoice->project->client->name ?? '-' }}<br>
                <strong>Audience:</strong> {{ $snapshot['audience'] ?? $invoice->audience->value }}
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th style="text-align: right;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($snapshot['invoice_lines'] ?? [] as $line)
                <tr>
                    <td>{{ $line['description'] }}</td>
                    <td style="text-align: right;">Rp {{ number_format($line['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table style="margin-bottom: 0;">
            <tr>
                <td>Subtotal</td>
                <td class="amount">Rp {{ number_format($snapshot['subtotal'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            @if(isset($snapshot['discount']) && $snapshot['discount'] > 0)
            <tr>
                <td>Diskon</td>
                <td class="amount">- Rp {{ number_format($snapshot['discount'], 0, ',', '.') }}</td>
            </tr>
            @endif
            @if(isset($snapshot['tax']) && $snapshot['tax'] > 0)
            <tr>
                <td>Pajak</td>
                <td class="amount">Rp {{ number_format($snapshot['tax'], 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>TOTAL</strong></td>
                <td class="amount" style="font-size: 16px; color: #2563eb;">Rp {{ number_format($snapshot['total'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div style="clear: both; margin-top: 30px;">
        <p><strong>Pembayaran ditransfer ke:</strong></p>
        <p>
            Bank: {{ $snapshot['bank_account']['bank_name'] ?? '-' }}<br>
            No. Rekening: {{ $snapshot['bank_account']['account_number'] ?? '-' }}<br>
            A.n: {{ $snapshot['bank_account']['account_holder'] ?? '-' }}
        </p>
    </div>

    <div class="footer">
        {{ $snapshot['footer_note'] ?? 'Terima kasih atas kepercayaan Anda kepada kami.' }}
    </div>
</body>
</html>
