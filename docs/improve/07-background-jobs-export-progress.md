# Plan 07 - Background Jobs, Export, dan Progress Tracker

## Tujuan

Memindahkan operasi berat ke queue agar request user cepat selesai, lalu memberi progress/notification saat pekerjaan selesai.

## Prioritas

P2. Penting untuk reliabilitas dan UX, tetapi menyentuh backend, storage, queue worker, dan frontend.

## Sumber Dari new_improve.md

- Backend: Asynchronous Queues
- Phase 3: Full Background Queues
- Phase 3: UI progress tracker

## Baseline Repo

Repo sudah memiliki:

- Notification outbox dan beberapa command queueing.
- Health/metrics untuk queue.
- Deployment workflow yang menyertakan queue restart.
- POS offline sync queue.

Plan ini fokus ke operasi berat yang masih berjalan sinkron.

## Scope

Termasuk:

- Audit export PDF/Excel/report yang masih sinkron.
- Buat job queue untuk export massal atau proses integrasi berat.
- Simpan status pekerjaan agar user bisa cek progress.
- Tambah notifikasi atau notification bell saat file siap.
- Pastikan job idempotent dan punya retry/backoff.

Tidak termasuk:

- Migrasi semua proses write kecil ke queue.
- Mengganti provider queue atau menambah dependency baru tanpa persetujuan.

## Kandidat Operasi Berat

- Export PDF/Excel massal.
- Kalkulasi eFaktur bulanan.
- Sinkronisasi pihak ketiga.
- Email/WhatsApp/push massal.
- Recalculate report finansial atau SHU.

## Desain Minimum

Gunakan tabel status job jika belum ada pola reusable:

- `id`
- `user_id`
- `type`
- `status`: pending, processing, completed, failed
- `progress`
- `file_path`
- `error_message`
- `metadata`
- timestamps

Jika sudah ada model/pola sejenis, pakai yang existing.

## Langkah Implementasi

1. Pilih satu operasi berat sebagai pilot, idealnya export report yang sering dipakai.
2. Ubah endpoint dari generate langsung menjadi enqueue job dan return status id.
3. Buat job dengan `ShouldQueue`, retry/backoff, dan guard idempotency jika perlu.
4. Simpan output ke storage private.
5. Buat endpoint untuk cek status dan download file selesai.
6. Tambah UI progress tracker atau notification item.
7. Tambah test:
   - enqueue berhasil.
   - job menghasilkan file/status completed.
   - download ditolak jika bukan owner.
   - failure menyimpan pesan error aman.

## Acceptance Criteria

- Request enqueue cepat dan tidak menunggu proses export selesai.
- User bisa melihat status pending/processing/completed/failed.
- File hanya bisa diunduh oleh user berwenang.
- Job aman di-retry.
- Queue failure terlihat di monitoring existing.

## Verifikasi Minimal

- Test feature export/job yang disentuh.
- Test queue dengan `Queue::fake()` untuk enqueue dan test job langsung untuk output.
- `vendor/bin/pint --dirty --format agent`
- Manual smoke dengan queue worker lokal bila memungkinkan.

## Risiko

- File export private harus dijaga agar tidak bisa diakses publik.
- Job yang tidak idempotent bisa membuat output ganda saat retry.
- Progress palsu lebih buruk daripada tidak ada progress. Jika progress granular belum tersedia, tampilkan status tahap saja.

