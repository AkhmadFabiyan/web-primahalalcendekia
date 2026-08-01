<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Management Summary Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; color: #15803d; }
        .info { margin-bottom: 20px; }
        .info table { width: 100%; border: none; }
        .info td { padding: 3px 0; }
        .info strong { display: inline-block; width: 150px; }
        .kpi-container { width: 100%; margin-bottom: 20px; }
        .kpi-box { width: 23%; display: inline-block; border: 1px solid #ddd; padding: 10px; margin-right: 1%; box-sizing: border-box; text-align: center; border-radius: 5px; }
        .kpi-title { font-size: 10px; color: #666; margin-bottom: 5px; }
        .kpi-value { font-size: 16px; font-weight: bold; color: #15803d; }
        h2 { font-size: 14px; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 20px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        table.data th { background-color: #f9fafb; font-weight: bold; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; height: 30px; text-align: right; font-size: 10px; color: #999; }
        .page-number:before { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        Halaman <span class="page-number"></span>
    </div>

    <div class="header">
        <h1>Laporan Ringkasan Manajemen</h1>
        <p>Prima Halal Cendekia</p>
    </div>

    <div class="info">
        <table>
            <tr>
                <td><strong>Periode</strong></td>
                <td>: {{ \Carbon\Carbon::parse($filterData->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($filterData->end_date)->format('d M Y') }}</td>
            </tr>
            <tr>
                <td><strong>Dibuat Oleh</strong></td>
                <td>: {{ $user->name }}</td>
            </tr>
            <tr>
                <td><strong>Waktu Pembuatan</strong></td>
                <td>: {{ $generatedAt->format('d M Y H:i:s') }}</td>
            </tr>
            <tr>
                <td><strong>Filter Aktif</strong></td>
                <td>: 
                    {{ $filterData->marketing_id ? 'Marketing ID: ' . $filterData->marketing_id . ', ' : '' }}
                    {{ $filterData->client_type ? 'Tipe: ' . $filterData->client_type . ', ' : '' }}
                    {{ $filterData->preset ? 'Preset: ' . $filterData->preset : '' }}
                </td>
            </tr>
        </table>
    </div>

    <h2>KPI Utama</h2>
    <div class="kpi-container">
        <div class="kpi-box">
            <div class="kpi-title">TOTAL LEAD</div>
            <div class="kpi-value">{{ number_format($kpi['total_lead']) }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">LEAD DEAL</div>
            <div class="kpi-value">{{ number_format($kpi['lead_deal']) }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">CONVERSION RATE</div>
            <div class="kpi-value">{{ $kpi['conversion_rate'] }}%</div>
        </div>
        <div class="kpi-box" style="margin-right: 0;">
            <div class="kpi-title">PROJECT AKTIF</div>
            <div class="kpi-value">{{ number_format($kpi['project_aktif']) }}</div>
        </div>
    </div>
    
    <div class="kpi-container">
        <div class="kpi-box">
            <div class="kpi-title">SERTIFIKAT TERBIT</div>
            <div class="kpi-value">{{ number_format($kpi['sertifikat_terbit']) }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">PROJECT SELESAI</div>
            <div class="kpi-value">{{ number_format($kpi['project_selesai']) }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">KAS MASUK (VERIFIED)</div>
            <div class="kpi-value">Rp {{ number_format($kpi['kas_masuk']) }}</div>
        </div>
        <div class="kpi-box" style="margin-right: 0;">
            <div class="kpi-title">OUTSTANDING</div>
            <div class="kpi-value">Rp {{ number_format($kpi['outstanding']) }}</div>
        </div>
    </div>

    <h2>Cycle Time & Completion Metrics</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Metrik Siklus</th>
                <th>Nilai (Hari)</th>
                <th>Metrik Penyelesaian</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Rata-rata (Avg)</td>
                <td>{{ $cycleMetrics['avg'] }}</td>
                <td>Sertifikasi (Certification Rate)</td>
                <td>{{ $completionMetrics['certification_rate'] }}%</td>
            </tr>
            <tr>
                <td>Median</td>
                <td>{{ $cycleMetrics['median'] }}</td>
                <td>Penyelesaian (Closure Rate)</td>
                <td>{{ $completionMetrics['closure_rate'] }}%</td>
            </tr>
            <tr>
                <td>Persentil ke-75</td>
                <td>{{ $cycleMetrics['p75'] }}</td>
                <td>Pembatalan (Cancellation Rate)</td>
                <td>{{ $completionMetrics['cancellation_rate'] }}%</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 10px; color: #666; text-align: center;">
        <p>Laporan ini digenerate secara otomatis oleh sistem. Untuk data detail, silakan gunakan fitur ekspor CSV/XLSX pada aplikasi.</p>
    </div>
</body>
</html>
