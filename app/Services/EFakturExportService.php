<?php

namespace App\Services;

use App\Models\Invoice;

class EFakturExportService
{
    public function generateCsv(Invoice $invoice): string
    {
        $rows = [];

        $masa = (int) date('n', strtotime($invoice->invoice_date ?? now()));
        $tahun = (int) date('Y', strtotime($invoice->invoice_date ?? now()));
        $tanggal = date('d/m/Y', strtotime($invoice->invoice_date ?? now()));

        $npwp = preg_replace('/[^0-9]/', '', $invoice->client->tax_id ?? '');
        $nama = $invoice->client->name ?? '';
        $alamat = $invoice->client->address ?? '';

        $dpp = number_format((float) ($invoice->amount ?? 0), 2, '.', '');
        $ppn = number_format((float) ($invoice->tax_amount ?? 0), 2, '.', '');
        $ppnbm = number_format(0, 2, '.', '');

        $rows[] = [
            'KD_JENIS_TRANSAKSI' => '01',
            'FG_PENGGANTI' => '0',
            'NOMOR_FAKTUR' => $invoice->invoice_no ?? '',
            'MASA_PAJAK' => $masa,
            'TAHUN_PAJAK' => $tahun,
            'TANGGAL_FAKTUR' => $tanggal,
            'NPWP' => $npwp,
            'NAMA' => $nama,
            'ALAMAT_LENGKAP' => $alamat,
            'JUMLAH_DPP' => $dpp,
            'JUMLAH_PPN' => $ppn,
            'JUMLAH_PPNBM' => $ppnbm,
            'ID_KETERANGAN_TAMBAHAN' => '',
            'FG_UANG_MUKA' => '0',
            'UANG_MUKA_DPP' => number_format(0, 2, '.', ''),
            'UANG_MUKA_PPN' => number_format(0, 2, '.', ''),
            'UANG_MUKA_PPNBM' => number_format(0, 2, '.', ''),
            'REFERENSI' => $invoice->project_id ? ('PRJ-'.$invoice->project_id) : '',
        ];

        $header = array_keys($rows[0]);
        $csv = implode(',', $header)."\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map([$this, 'escape'], $row))."\n";
        }

        return $csv;
    }

    private function escape($value): string
    {
        $v = (string) $value;
        if (str_contains($v, ',') || str_contains($v, '"') || str_contains($v, "\n")) {
            $v = '"'.str_replace('"', '""', $v).'"';
        }

        return $v;
    }
}
