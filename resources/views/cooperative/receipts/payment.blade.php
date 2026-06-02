<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $receipt->receipt_no }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        h1 {
            font-size: 22px;
            margin: 0 0 4px;
        }

        table {
            border-collapse: collapse;
            margin-top: 24px;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            width: 34%;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
        }

        .amount {
            font-size: 18px;
            font-weight: 700;
        }

        .footer {
            margin-top: 42px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kuitansi Pembayaran Koperasi</h1>
        <div>No. {{ $receipt->receipt_no }}</div>
    </div>

    <table>
        <tr>
            <th>Anggota</th>
            <td>{{ $payment->member?->member_no }} - {{ $payment->member?->name }}</td>
        </tr>
        <tr>
            <th>Jenis Tagihan</th>
            <td>{{ $payment->invoice?->contributionType?->name ?? 'Pembayaran koperasi' }}</td>
        </tr>
        <tr>
            <th>Periode</th>
            <td>{{ $payment->invoice?->period ?? $payment->paid_at?->format('Y-m') }}</td>
        </tr>
        <tr>
            <th>Tanggal Bayar</th>
            <td>{{ $payment->paid_at?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Metode</th>
            <td>{{ $payment->payment_method }}</td>
        </tr>
        <tr>
            <th>Referensi</th>
            <td>{{ $payment->reference_no ?? '-' }}</td>
        </tr>
        <tr>
            <th>Jumlah</th>
            <td class="amount">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        <div>Diterbitkan pada {{ $receipt->issued_at?->format('d/m/Y H:i') }}</div>
        <strong>Koperasi Kojaya</strong>
    </div>
</body>
</html>
