# Backup / Restore Runbook — Kojaya

## Ringkasan

Dokumen ini berisi prosedur standar backup dan restore untuk aplikasi Kojaya.
Semua perintah dijalankan di server produksi atau environment yang sesuai.

**Frekuensi Backup:**
- Database: harian (full dump), setiap 6 jam (incremental WAL untuk PostgreSQL)
- File storage: harian (payslip, KYC, sertifikat, medical checkup, lampiran)
- Konfigurasi: setiap perubahan (`.env`, `nginx`, `supervisor`)

## 1. Backup Database

### 1.1 MySQL / MariaDB

```bash
mysqldump \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --no-tablespaces \
  -u ${DB_USERNAME} \
  -p${DB_PASSWORD} \
  ${DB_DATABASE} | gzip > /backup/db/kojaya-$(date +%Y%m%d-%H%M).sql.gz
```

### 1.2 PostgreSQL

```bash
pg_dump \
  -U ${DB_USERNAME} \
  -h ${DB_HOST} \
  -Fc \
  ${DB_DATABASE} > /backup/db/kojaya-$(date +%Y%m%d-%H%M).dump
```

### 1.3 SQLite

```bash
cp database/database.sqlite /backup/db/kojaya-$(date +%Y%m%d-%H%M).sqlite
gzip /backup/db/kojaya-$(date +%Y%m%d-%H%M).sqlite
```

## 2. Backup File Storage

### 2.1 Public & Private Files

```bash
tar -czf /backup/files/storage-$(date +%Y%m%d-%H%M).tar.gz \
  storage/app/public/payslips \
  storage/app/public/documents \
  storage/app/public/certificates \
  storage/app/public/kyc \
  storage/app/public/attachments \
  storage/app/public/medical
```

### 2.2 Konfigurasi Aplikasi

```bash
tar -czf /backup/config/config-$(date +%Y%m%d-%H%M).tar.gz \
  .env \
  .env.example \
  storage/oauth-private.key \
  storage/oauth-public.key
```

## 3. Backup Otomatis via Cron

Tambahkan ke crontab:

```cron
# Database backup setiap hari jam 2 pagi
0 2 * * * /usr/local/bin/backup-kojaya-db.sh

# File storage backup setiap hari jam 3 pagi
0 3 * * * /usr/local/bin/backup-kojaya-files.sh
```

## 4. Verifikasi Backup

### 4.1 Validasi Dump SQL

```bash
gunzip -c /backup/db/kojaya-*.sql.gz | head -20
echo "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM cooperative_members;" | \
  mysql -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE}
```

### 4.2 Validasi Storage

```bash
tar -tzf /backup/files/storage-*.tar.gz | wc -l
```

### 4.3 Cek Ukuran

```bash
ls -lh /backup/db/ /backup/files/ /backup/config/
```

## 5. Restore Database

### 5.1 Prasyarat

1. Hentikan queue worker: `php artisan queue:restart && supervisorctl stop kojaya-worker`
2. Aktifkan maintenance mode: `php artisan down`
3. Backup database saat ini (untuk rollback): jalankan section 1

### 5.2 MySQL

```bash
gunzip -c /backup/db/kojaya-20260507-020000.sql.gz | \
  mysql -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE}
```

### 5.3 PostgreSQL

```bash
pg_restore \
  -U ${DB_USERNAME} \
  -h ${DB_HOST} \
  -d ${DB_DATABASE} \
  --clean \
  --if-exists \
  /backup/db/kojaya-20260507-020000.dump
```

### 5.4 SQLite

```bash
# Hentikan aplikasi terlebih dahulu
cp /backup/db/kojaya-20260507-020000.sqlite database/database.sqlite
```

## 6. Restore File Storage

```bash
tar -xzf /backup/files/storage-20260507-030000.tar.gz -C /
```

## 7. Restore Konfigurasi

```bash
tar -xzf /backup/config/config-20260507-030000.tar.gz -C /
php artisan config:clear
php artisan config:cache
```

## 8. Pasca-Restore

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
supervisorctl start kojaya-worker
```

## 9. Test Restore Berkala

Lakukan test restore ke environment staging minimal **sebulan sekali**:

```bash
# Di environment staging:
php artisan down --env=staging
# Jalankan langkah 5-8
# Verifikasi:
php artisan tinker --env=staging
> User::count();
> CooperativeMember::count();
> Payroll::where('period', '2026-04')->count();
> Loan::where('status', 'ACTIVE')->count();
# Test login dan akses halaman utama
php artisan up --env=staging
```

## 10. Retensi Backup

| Jenis | Retensi | Keterangan |
|---|---|---|
| Database full dump | 30 hari | Hapus backup > 30 hari |
| Database incremental | 7 hari | WAL files |
| File storage | 30 hari | Sinkron dengan retensi DB |
| Konfigurasi | 90 hari | Simpan versi lama, hapus > 90 hari |
| Audit log | 1 tahun | Export ke cold storage sebelum hapus |

Hapus backup lama:

```bash
find /backup/db/ -name "*.sql.gz" -mtime +30 -delete
find /backup/files/ -name "*.tar.gz" -mtime +30 -delete
find /backup/config/ -name "*.tar.gz" -mtime +90 -delete
```

## 11. Checklist Insiden

Saat insiden produksi, urutan prioritas:

1. **Simpan bukti**: screenshot, log, stack trace
2. **Identifikasi skop**: modul apa, berapa user terdampak
3. **Tentukan roll-forward atau rollback**:
   - Data corrupt minor → perbaiki data via tinker / migration hotfix
   - Data corrupt mayor → restore DB dari backup terbaru
   - File corrupt → restore storage dari backup terbaru
4. **Eksekusi restore** (section 5-8)
5. **Verifikasi**: jalankan test restore (section 9)
6. **Post-mortem**: catat penyebab, solusi, dan pencegahan di `docs/log.md`

## 12. Kontak Darurat

- **DevOps Lead**: [isi sesuai tim]
- **DBA**: [isi sesuai tim]
- **Backup Server**: [isi IP / hostname]
- **Offsite Backup**: [isi lokasi cold storage]
