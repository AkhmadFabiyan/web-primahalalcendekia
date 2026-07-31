<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header td { vertical-align: top; }
        .company-name { font-size: 24px; font-weight: bold; color: #2563eb; }
        .invoice-title { font-size: 20px; font-weight: bold; text-align: right; color: #333; }
        .details { width: 100%; margin-bottom: 30px; }
        .details td { vertical-align: top; width: 50%; }
        .items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items th, .items td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .items th { background-color: #f8fafc; font-weight: bold; }
        .totals { width: 100%; border-collapse: collapse; }
        .totals td { padding: 5px 10px; text-align: right; }
        .totals .bold { font-weight: bold; }
        .notes { margin-top: 40px; font-size: 11px; color: #666; }
        .watermark { 
            position: absolute; top: 30%; left: 15%; font-size: 80px; 
            color: rgba(239, 68, 68, 0.2); transform: rotate(-45deg); 
            z-index: -1; pointer-events: none;
        }
    </style>
</head>
<body>
    @if($invoice->status === \App\Modules\Payments\Enums\InvoiceStatus::CANCELLED)
        <div class="watermark">CANCELLED</div>
    @endif

    <table class="header">
        <tr>
            <td>
                <div class="company-name">PHC System</div>
                <div>PT Prima Halal Cendekia</div>
            </td>
            <td>
                <div class="invoice-title">INVOICE</div>
                <div style="text-align: right;">No: {{ $invoice->invoice_number ?? 'DRAFT' }}</div>
                <div style="text-align: right;">Tgl Terbit: {{ $invoice->issued_at ? $invoice->issued_at->format('d/m/Y') : '-' }}</div>
                <div style="text-align: right;">Jatuh Tempo: {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</div>
            </td>
        </tr>
    </table>

    <table class="details">
        <tr>
            <td>
                <strong>Ditagihkan Kepada:</strong><br>
                {{ $snapshot['billing_target_name'] ?? '-' }}<br>
                PIC: {{ $snapshot['pic_name'] ?? '-' }} ({{ $snapshot['pic_phone'] ?? '-' }})<br>
                {{ $snapshot['address'] ?? '-' }}, {{ $snapshot['city'] ?? '-' }}, {{ $snapshot['province'] ?? '-' }}
            </td>
            <td>
                <strong>Informasi Proyek:</strong><br>
                Layanan: {{ $snapshot['project_service_type'] ?? '-' }}<br>
                Tipe Tagihan: {{ $invoice->invoice_type->name ?? '-' }}<br>
                ID Klien: {{ $snapshot['client_id'] ?? '-' }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th style="text-align: right; width: 150px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Layanan {{ $snapshot['project_service_type'] ?? 'Jasa' }} ({{ $invoice->invoice_type->name ?? 'Aktivasi' }})</td>
                <td style="text-align: right;">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td style="width: 70%;">Subtotal:</td>
            <td style="width: 30%;">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Diskon:</td>
            <td>Rp {{ number_format($invoice->discount_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="bold" style="font-size: 14px;">Total Tagihan:</td>
            <td class="bold" style="font-size: 14px;">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="notes">
        <strong>Catatan:</strong><br>
        {{ $invoice->notes ?: 'Tidak ada catatan tambahan.' }}
        @if($invoice->status === \App\Modules\Payments\Enums\InvoiceStatus::CANCELLED)
            <br><br><strong style="color: red;">Alasan Pembatalan:</strong><br>
            {{ $invoice->cancel_reason }}
        @endif
    </div>
</body>
</html>
