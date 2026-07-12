# Senior Instruction to Codex

Terima kasih. Implementasi yang sudah masuk merupakan kemajuan besar, terutama permission split, member active gate awal, revocation service, maker-checker, transaction wrapping, API masking, reservation, dan audit contract.

Namun setelah senior review terhadap head `b89f04bd4a709de4e1ee9fa8bfc7eb2bc207b895`, keputusan saat ini adalah:

> **REQUEST CHANGES. P0/P1 belum dapat dinyatakan selesai. Tetap Draft.**

Sebelum mengerjakan encryption/blind index P2, kerjakan blocker berikut secara berurutan:

1. Perbaiki CI Wayfinder drift sampai PHPUnit benar-benar berjalan.
2. Tutup active-gate bypass pada payment charge, points, dan rewards.
3. Satukan lifecycle status + validation_status melalui satu transition service.
4. Pastikan resign/deactivate menutup akses browser session, bukan hanya Sanctum token.
5. Terapkan organization scope pada policy dan direct-object action, bukan list saja.
6. Tutup unsafe member-user linking terhadap privileged/cross-org user.
7. Persempit revocation agar token ESS/technician tidak ikut terhapus.
8. Tambahkan negative tests dan true concurrency tests sesuai matriks.
9. Pecah PR menjadi focused PRs; jangan ajukan 67-file integration branch sebagai final merge.

Setelah item 1–8 selesai dan CI penuh hijau, ajukan kembali sebagai:

```text
READY-FOR-SENIOR-REVIEW: P0/P1 closure
```

P2 final baru dimulai setelah sign-off P0/P1. Gunakan dokumen:

- `01-P0-P1-BLOCKER-REMEDIATION-PLAN.md`
- `02-P2-FINAL-HARDENING-PLAN.md`
- `03-SECURITY-REGRESSION-TEST-MATRIX.md`
- `04-PR-SPLIT-AND-REVIEW-PROTOCOL.md`

Jangan mengubah README roadmap menjadi selesai sebelum seluruh Definition of Done senior terpenuhi.
