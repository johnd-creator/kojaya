<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan POS</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px 0; }
        h2 { font-size: 14px; margin: 18px 0 6px 0; color: #1d4ed8; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
        .right { text-align: right; }
        .summary-grid { width: 100%; margin-top: 8px; }
        .summary-grid td { border: 1px solid #e5e7eb; padding: 10px; width: 25%; background: #f9fafb; }
        .summary-grid .label { font-size: 9px; color: #6b7280; text-transform: uppercase; }
        .summary-grid .value { font-size: 14px; font-weight: bold; margin-top: 4px; }
    </style>
</head>
<body>
    <h1>Laporan POS</h1>
    <p class="muted">Periode {{ $from }} sd {{ $to }}</p>

    <h2>Ringkasan</h2>
    <table class="summary-grid">
        <tr>
            <td>
                <div class="label">Transaksi</div>
                <div class="value">{{ number_format($summary['transactions']) }}</div>
            </td>
            <td>
                <div class="label">Penjualan Kotor</div>
                <div class="value">Rp {{ number_format($summary['gross_sales'], 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="label">Laba Kotor</div>
                <div class="value">Rp {{ number_format($summary['gross_profit'], 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="label">Penjualan Bersih</div>
                <div class="value">Rp {{ number_format($summary['net_sales'], 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <h2>Rekonsiliasi Pembayaran</h2>
    <table>
        <thead><tr><th>Metode</th><th class="right">Jumlah</th><th class="right">Total</th></tr></thead>
        <tbody>
        @foreach($paymentReconciliation as $row)
            <tr><td>{{ $row['method'] }}</td><td class="right">{{ number_format($row['count']) }}</td><td class="right">Rp {{ number_format($row['total'], 0, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <h2>15 Produk Teratas</h2>
    <table>
        <thead><tr><th>Produk</th><th class="right">Qty</th><th class="right">Pendapatan</th><th class="right">Laba</th><th class="right">Margin</th></tr></thead>
        <tbody>
        @foreach($topProducts as $row)
            <tr>
                <td>{{ $row['product_name'] }}</td>
                <td class="right">{{ number_format($row['quantity']) }}</td>
                <td class="right">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['margin_percent'], 1) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Tren Harian</h2>
    <table>
        <thead><tr><th>Tanggal</th><th class="right">Transaksi</th><th class="right">Pendapatan</th></tr></thead>
        <tbody>
        @foreach($dailyTrend as $row)
            <tr><td>{{ $row['date'] }}</td><td class="right">{{ number_format($row['transactions']) }}</td><td class="right">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
