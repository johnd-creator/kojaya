# Data Governance — Aplikasi Kojaya

Dokumen terakhir diperbarui: 7 Mei 2026

## 1. Field yang Wajib Di-encrypt At Rest

Berdasarkan audit model per 7 Mei 2026, saat ini **tidak ada field-level encryption** di database. Field berikut direkomendasikan untuk di-encrypt:

| Prioritas | Model | Field | Alasan |
|---|---|---|---|
| KRITIS | Employee | `bank_account_number` | Rekening bank pegawai |
| KRITIS | Employee | `npwp_number` | NPWP pegawai |
| KRITIS | EmployeeFamily | `nik_ktp` | NIK anggota keluarga (unique constraint) |
| KRITIS | CooperativeMember | `identity_number` | NIK anggota koperasi |
| KRITIS | Vendor | `bank_account_no` | Rekening vendor |
| TINGGI | Employee | `bank_account_holder` | Nama pemilik rekening |
| TINGGI | Vendor | `bank_account_name` | Nama pemilik rekening vendor |
| TINGGI | Vendor | `tax_id` | NPWP vendor |
| TINGGI | Payroll | `pph21_calculation_breakdown` | JSON breakdown pajak (mengandung NPWP info) |

### Implementasi yang Direkomendasikan

Gunakan Laravel native encryption via `Crypt` facade dalam Eloquent cast:

```php
// app/Models/Employee.php
protected function casts(): array
{
    return [
        'bank_account_number' => 'encrypted',
        'npwp_number' => 'encrypted',
        'bank_account_holder' => 'encrypted',
    ];
}
```

**Catatan**: Field yang digunakan untuk pencarian/search (seperti `identity_number` pada `CooperativeMember`) tidak bisa langsung di-encrypt karena Eloquent tidak bisa melakukan `WHERE` pada kolom terenkripsi. Solusi alternatif:
- Gunakan blind index dengan HMAC hash untuk pencarian
- Simpan hash partial (misalnya 4 digit terakhir plaintext) untuk validasi duplikasi

## 2. Kebijakan Retensi Data

| Kategori | Periode Retensi | Tindakan Setelah Expired |
|---|---|---|
| Device Token (`mobile_device_tokens`) | 6 bulan sejak `last_seen_at` | Soft delete/revoke, hapus permanen setelah 12 bulan |
| Audit Log (`audit_logs`) | 2 tahun | Arsipkan ke cold storage, hapus dari DB operasional |
| Approval Log (`approval_logs`) | 3 tahun | Arsipkan ke cold storage |
| Attachment dokumen (certificate, MCU, KYC) | Sesuai masa berlaku + 1 tahun | Hapus dari storage setelah masa retensi |
| Push Log (`push_notification_logs`) | 30 hari | Hapus permanen |
| Webhook Log (`webhook_logs`) | 90 hari | Hapus permanen |
| File temporary / staging | 7 hari | Hapus permanen via scheduled job |
| Session / token expired | 24 jam setelah expired | Hapus permanen |

## 3. Kontrol Akses Dokumen Sensitif

### 3.1 Signed URL untuk File Sensitif

Dokumen-dokumen berikut TIDAK BOLEH disajikan via URL publik (`/storage/...`). Dokumen harus diakses melalui controller dengan signed URL:

| Dokumen | Endpoint Signed URL | Permission Diperlukan |
|---|---|---|
| Payslip PDF | `/ess/payslip/{payroll}/download` | `view_own_payslip` atau `view_payroll_all` |
| Medical Checkup | `/mcu/{mcu}/download` | `view_employee_unit` + ownership check |
| Employee Certificate | `/employee/{employee}/certificate/{certificate}/download` | `view_employee_unit` + ownership check |
| KYC Document | `/member/{member}/document/{document}/download` | `view_cooperative_member` |

### 3.2 Download Audit

Setiap download dokumen sensitif wajib dicatat dalam `download_logs`:

```php
DownloadLog::create([
    'user_id' => auth()->id(),
    'document_type' => 'payslip', // payslip | certificate | mcu | kyc
    'document_id' => $payroll->id,
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

## 4. Backup / Restore Runbook

### 4.1 Frekuensi Backup

| Jenis | Frekuensi | Retensi |
|---|---|---|
| Database penuh | Harian (01:00 WIB) | 30 hari |
| File storage (`storage/app/`) | Harian (02:00 WIB) | 7 hari |
| Konfigurasi (`.env`) | Mingguan | 90 hari |

### 4.2 Prosedur Restore

1. Stop aplikasi (maintenance mode): `php artisan down`
2. Restore database: `mysql -u {user} -p {database} < backup.sql`
3. Restore file storage: `rsync -av backup/storage/ storage/app/`
4. Clear cache: `php artisan optimize:clear`
5. Start aplikasi: `php artisan up`
6. Verifikasi: Jalankan `php artisan test --filter=HealthCheckTest`

### 4.3 Test Restore Berkala

- Test restore database dari backup terakhir ke environment staging **setiap 2 minggu**
- Catat hasil dan waktu restore di log

## 5. Akses Data Berdasarkan Role

| Role | Payslip | Medical | Certificate | KYC | Financial Report | Audit Log |
|---|---|---|---|---|---|---|
| System Admin | ✅ all | ✅ all | ✅ all | ✅ all | ✅ all | ✅ all |
| Admin Pusat | ✅ all | ✅ all | ✅ all | ✅ all | ✅ all | ✅ all |
| HR | ✅ unit | — | ✅ unit | — | — | — |
| Finance | — | — | — | — | ✅ all | ✅ all |
| Pengurus Koperasi | — | — | — | ✅ member own | ✅ all | ✅ all |
| Kasir Koperasi | — | — | — | ✅ member own | — | — |
| Anggota | ✅ own | — | — | ✅ own | — | — |
| Employee | ✅ own | ✅ own | ✅ own | — | — | — |
| Technician | — | — | — | — | — | — |

## 6. Privacy Hardening Checklist

- [ ] Encrypt `bank_account_number` dan `npwp_number` pada Employee
- [ ] Encrypt `nik_ktp` pada EmployeeFamily
- [ ] Encrypt `identity_number` pada CooperativeMember (dengan blind index)
- [ ] Encrypt `bank_account_no` pada Vendor
- [ ] Migrasi file sensitif dari `public` disk ke `local` disk
- [ ] Implementasi signed URL untuk semua download sensitif
- [ ] Implementasi `DownloadLog` audit untuk payslip, certificate, MCU, KYC
- [ ] Scheduled job untuk retensi data (device token, push log, webhook log)
- [ ] Scheduled job untuk pembersihan file temporary
- [ ] Test restore database berkala
- [ ] Role-based access matrix untuk export/download data sensitif
