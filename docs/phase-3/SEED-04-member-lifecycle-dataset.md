# SEED-04: Member Lifecycle Dataset

## 1. Ringkasan Eksekutif

Dokumen ini mendefinisikan implementasi teknis seeder dataset siklus hidup anggota (*member lifecycle dataset*) untuk Phase 3 KojayaPro dan Kojayaku. Implementasi ini merealisasikan spesifikasi kontrak data pengujian pada [SEED-01 Test Data Contract](./SEED-01-test-data-contract.md), topologi organisasi [SEED-02R1 Organization Semantics](./SEED-02-master-reference-data.md), serta melanjutkan data persona dari [SEED-03 User & Member Persona Seeder](./SEED-03-user-member-personas.md).

Dataset ini diimplementasikan melalui [`CooperativeMemberLifecycleSeeder`](../../database/seeders/CooperativeMemberLifecycleSeeder.php), yang bertugas memperkaya (*enrich*) 7 profil anggota kanonikal (`DEV-KOP-006` hingga `DEV-KOP-013`) yang dibuat oleh `CooperativePersonaSeeder` dengan metadata persetujuan pengurus, verifikasi admin, catatan revisi, dan alasan penolakan yang sepenuhnya deterministik tanpa membuat record pengguna atau anggota baru.

---

## 2. Matriks Siklus Hidup Anggota Kanonikal (Member Lifecycle Matrix)

Baseline dataset DEV memiliki tepat 7 record anggota koperasi (`CooperativeMember`), seluruhnya terikat ke organisasi induk koperasi `KOP-001`. Garis waktu kanonikal yang digunakan adalah `2026-06-01` dengan alur stempel waktu berjenjang:
- Pengajuan pendaftaran (*submitted*): `2026-06-01 08:00:00`
- Verifikasi staf admin koperasi (P04): `2026-06-01 09:00:00`
- Permintaan revisi (P08): `2026-06-01 09:30:00`
- Keputusan persetujuan / penolakan pengurus (P02): `2026-06-01 10:00:00`

### Rincian Persona Siklus Hidup

| Persona | No. Anggota | Status | Status Validasi | Experience Enum | Verifikasi Admin (P04) | Waktu Verifikasi | Pengesahan / Keputusan | Waktu Keputusan | Catatan Revisi / Penolakan / Pengesahan | Tanggal Aktif |
| :--- | :--- | :---: | :---: | :--- | :---: | :---: | :---: | :---: | :--- | :---: |
| **P06** | `DEV-KOP-006` | `PENDING` | `PENDING` | `WaitingVerification` | `null` | `null` | `null` | `null` | `null` | `null` |
| **P07** | `DEV-KOP-007` | `PENDING` | `PENDING_VALIDATION` | `UnderReview` | P04 | `09:00:00` | `null` | `null` | `null` | `null` |
| **P08** | `DEV-KOP-008` | `INACTIVE` | `REVISION` | `RevisionRequired` | P04 | `09:00:00` | P04 | `09:30:00` | Catatan revisi dokumen domisili/telepon | `null` |
| **P09** | `DEV-KOP-009` | `INACTIVE` | `REJECTED` | `Rejected` | P04 | `09:00:00` | P02 | `10:00:00` | Alasan penolakan syarat keanggotaan | `null` |
| **P10** | `DEV-KOP-010` | `ACTIVE` | `ACTIVE` | `Active` | P04 | `09:00:00` | P02 | `10:00:00` | Catatan persetujuan pengurus | `2026-06-01` |
| **P12** | `DEV-KOP-012` | `ACTIVE` | `ACTIVE` | `Active` | P04 | `09:00:00` | P02 | `10:00:00` | Catatan persetujuan pengurus | `2026-06-01` |
| **P13** | `DEV-KOP-013` | `ACTIVE` | `ACTIVE` | `Active` | P04 | `09:00:00` | P02 | `10:00:00` | Catatan persetujuan pengurus | `2026-06-01` |

*Keterangan Aktor & Riwayat Verifikasi (SEED-04R1):*
- **P04 (Admin Koperasi):** `seed.admin.kop@kojaya.test` bertindak sebagai verifikator berkas awal (`admin_validated_by`, `admin_validated_at` = `09:00:00`) pada P07, P08, P09, P10, P12, dan P13. Pada P08, P04 juga bertindak sebagai pihak yang meminta revisi (`validated_by` = P04, `validated_at` = `09:30:00`).
- **P02 (Pengurus Koperasi):** `seed.pengurus@kojaya.test` bertindak sebagai pengambil keputusan final (`validated_by` = P02, `validated_at` = `10:00:00`) pada penolakan P09 (dengan invariant *maker-checker* P04 != P02) dan pengesahan aktif P10, P12, P13.

---

## 3. Perilaku Route Gate & Pengalaman Anggota (Lifecycle Experience & Route Gate Behavior)

Setiap persona mencerminkan pengalaman anggota yang berbeda pada antarmuka web dan mobile:

### A. Rute Onboarding Web (`GET /member/onboarding`)
- **P06 (`WaitingVerification`):** Menampilkan status menunggu verifikasi (`review_state = 'pending'`), `can_edit_safe_profile = true`.
- **P07 (`UnderReview`):** Menampilkan status sedang ditinjau pengurus (`review_state = 'review'`), formulir dikunci (`can_edit_safe_profile = false`).
- **P08 (`RevisionRequired`):** Menampilkan catatan revisi dokumen (`review_state = 'revision'`), formulir dibuka untuk perbaikan (`can_edit_safe_profile = true`).
- **P09 (`Rejected`):** Menampilkan alasan penolakan pendaftaran (`review_state = 'rejected'`), formulir dikunci (`can_edit_safe_profile = false`).
- **P10, P12, P13 (`Active`):** Dialihkan langsung (*redirect*) ke dashboard anggota (`member.dashboard`).

### B. Pengiriman Pembaruan Profil Onboarding (`POST /member/onboarding`)
- **P06 & P08:** Diizinkan mengirimkan pembaruan data profil yang aman, sistem mengarahkan kembali ke onboarding tanpa mengubah otoritas lifecycle yang telah ada.
- **P07, P09, P10, P12, P13:** Ditolak dengan status **HTTP 403 Forbidden** oleh middleware / controller gate.

---

## 4. Factory State `blockedUnknown()` untuk Pengujian Batas

Sesuai kontrak SEED-01, status `P11` (`BLOCKED_UNKNOWN`) adalah kondisi anomali/inkonsisten yang **TIDAK BOLEH** ada dalam dataset awal lingkungan pengembangan (`DEV`). 

Namun, untuk mendukung unit dan feature testing terhadap penanganan anomali siklus hidup anggota, [`CooperativeMemberFactory`](../../database/factories/CooperativeMemberFactory.php) dilengkapi dengan state helper:

```php
public function blockedUnknown(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => CooperativeMemberStatus::INACTIVE->value,
        'validation_status' => MemberValidationStatus::INACTIVE->value,
    ]);
}
```

Ketika status di atas dievaluasi oleh `MemberLifecycleExperience::fromMember($member)`, nilai yang dihasilkan adalah `MemberLifecycleExperience::BlockedUnknown`. State ini hanya digunakan dalam pengujian terisolasi dan tidak dipanggil oleh seeder baseline lingkungan pengembangan.

---

## 5. Aturan Invarian & Keamanan (Safety Constraints)

1. **Zero New Personas / Entities:** `CooperativeMemberLifecycleSeeder` tidak membuat baris data baru pada tabel `users`, `cooperative_members`, ataupun tabel lainnya. Seeder ini murni memperkaya record yang sudah dibuat oleh `CooperativePersonaSeeder`.
2. **Zero Financial Fixtures:** Seeder tidak membuat mutasi buku besar, tagihan iuran, akun kredit toko, pinjaman, maupun transaksi kasir.
3. **Zero Token Side-Effects:** Seeder tidak membuat token otentikasi Sanctum di muka.
4. **Idempotensi & Auto-Repair:** Seeder dapat dijalankan berulang kali secara aman (`php artisan db:seed --class=CooperativeMemberLifecycleSeeder`). Jika terdapat perubahan manual atau manipulasi data pada metadata lifecycle anggota kanonikal, seeder akan memulihkan data kembali ke nilai deterministik kanonikal.
5. **Fail-Closed Environment Guard:** Seeder dilindungi oleh pengaman lingkungan:
   ```php
   if (! in_array((string) config('app.env'), ['local', 'testing', 'playwright'], true)) {
       throw new LogicException('CooperativeMemberLifecycleSeeder is only available in local, testing, or playwright environments.');
   }
   ```
6. **Hierarki Organisasi Tunggal:** Seluruh 7 anggota terikat secara eksklusif ke organisasi `KOP-001`. Entitas anak usaha `KBU-001` memiliki strictly 0 record anggota.
