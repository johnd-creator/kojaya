<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $transaction->transaction_no }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            max-width: 320px;
            margin: 12px auto;
            color: #111;
            font-size: 12px;
        }
        h1, h2, h3, p {
            margin: 0;
        }
        .center { text-align: center; }
        .row { display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { padding: 2px 0; text-align: left; }
        td.num, th.num { text-align: right; }
        hr { border: none; border-top: 1px dashed #555; margin: 6px 0; }
        .total { font-weight: bold; font-size: 14px; }
        .muted { color: #555; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
        .print-button {
            display: inline-block;
            padding: 8px 14px;
            background: #0f766e;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-family: sans-serif;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 12px;">
        <button class="print-button" onclick="window.print()">Cetak / Print</button>
    </div>
    <h2 class="center">KOPERASI KOJAYA</h2>
    <p class="center muted">Jl. Contoh No. 123, Jakarta</p>
    <hr>
    <div class="row">
        <span>No. Transaksi</span>
        <span>{{ $transaction->transaction_no }}</span>
    </div>
    <div class="row">
        <span>Tanggal</span>
        <span>{{ $transaction->sold_at->format('d-m-Y H:i') }}</span>
    </div>
    <div class="row">
        <span>Kasir</span>
        <span>{{ $transaction->cashier?->name ?? '—' }}</span>
    </div>
    @if($transaction->member)
    <div class="row">
        <span>Anggota</span>
        <span>{{ $transaction->member->member_no ?? $transaction->member->name }}</span>
    </div>
    @endif
    <hr>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="num">Qty</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? 'Item' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <hr>
    <div class="row">
        <span>Subtotal</span>
        <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
    </div>
    @if((float) $transaction->discount_amount > 0)
    <div class="row">
        <span>Diskon</span>
        <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
    </div>
    @endif
    <div class="row total">
        <span>Total</span>
        <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
    </div>
    <hr>
    @foreach($transaction->payments as $payment)
    <div class="row">
        <span>{{ $payment->payment_method }}{{ $payment->reference_no ? " ({$payment->reference_no})" : '' }}</span>
        <span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
    </div>
    @endforeach
    @if($transaction->cash_received !== null)
    <div class="row">
        <span>Tunai Diterima</span>
        <span>Rp {{ number_format($transaction->cash_received, 0, ',', '.') }}</span>
    </div>
    <div class="row">
        <span>Kembalian</span>
        <span>Rp {{ number_format($transaction->cash_change ?? 0, 0, ',', '.') }}</span>
    </div>
    @endif
    <hr>
    <p class="center muted">Terima kasih telah berbelanja</p>
    <p class="center muted" style="font-size: 10px;">{{ now()->format('d-m-Y H:i:s') }}</p>
    <script>
        if (window.location.search.includes('autoprint=1')) {
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        }
    </script>
</body>
</html>
