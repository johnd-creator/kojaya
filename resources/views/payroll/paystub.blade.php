<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Paystub - {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0 0;
            color: #666;
        }

        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }

        .info-table td {
            padding: 5px;
        }

        .info-table .label {
            font-weight: bold;
            width: 150px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .details-table th,
        .details-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .details-table th {
            text-align: left;
            background-color: #f8f9fa;
        }

        .amount {
            text-align: right;
        }

        .net-pay {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            padding: 15px;
            border-top: 2px solid #333;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $payroll->organization->name }}</h1>
        <p>Slip Gaji / Paystub</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Pegawai:</td>
            <td>{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</td>
            <td class="label">Periode:</td>
            <td>{{ $payroll->period }}</td>
        </tr>
        <tr>
            <td class="label">NIK / Karyawan ID:</td>
            <td>{{ $payroll->employee->employee_code ?? $payroll->employee->nik }}</td>
            <td class="label">Tanggal Cetak:</td>
            <td>{{ now()->format('d M Y') }}</td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th>Deskripsi Komponen</th>
                <th class="amount">Pendapatan / Potongan (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payroll->components as $component)
                <tr>
                    <td>{{ $component->description }}</td>
                    <td class="amount">{{ number_format($component->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="net-pay">
        Total Take Home Pay (Net): Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
    </div>

    <div class="footer">
        Dokumen ini dibuat otomatis oleh Sistem ERP dan sah tanpa tanda tangan basah.<br>
        {{ $payroll->organization->address ?? 'Alamat Kantor' }}
    </div>
</body>

</html>