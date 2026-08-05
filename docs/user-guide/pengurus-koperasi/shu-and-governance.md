---
title: SHU, Tata Kelola, dan Audit Internal
slug: pengurus-shu-and-governance
summary: Siklus SHU tahunan, audit internal, dan AD/ART.
category: Pengurus Koperasi · Tata Kelola
module: governance
roles:
  - pengurus_koperasi
permissions:
  - manage_cooperative_shu
permission_mode: all
route_names:
  - cooperative.shu.index
  - cooperative.shu.close
  - cooperative.shu.request-revision
  - cooperative.points.index
  - cooperative.reports.index
  - audit-logs
  - exceptions.index
risk_level: high
screenshot_entries:
  - pengurus-shu-and-governance-desktop
related_articles:
  - pengurus-loan-approval
last_reviewed_commit: 20c86960
status: published
sort_order: 20
---

# SHU, Tata Kelola, dan Audit Internal

## SHU tahunan

- Lihat periode SHU: `route('cooperative.shu.index')`.
- Tutup periode: `route('cooperative.shu.close')`.
- Minta revisi periode:
  `route('cooperative.shu.request-revision')`.

> **Catatan:** tidak ada preset "RAT" atau "AnnualReport" di
> `CooperativeReportController`. Penyaluran poin ke anggota
> dilakukan via `route('cooperative.points.index')` (perlu
> `manage_cooperative_points`).

## Audit internal

- Audit log: `route('audit-logs')` (perlu
  permission `view_audit_logs`).
- Pengawasan operasional: manfaatkan
  `route('exceptions.index')` untuk anomali lintas modul.
- Perubahan AD/ART: lewat modul pengaturan koperasi yang
  dilindungi permission `manage_cooperative_settings`
  (lihat `App\Enums\PermissionEnum::COOPERATIVE_SETTINGS_MANAGE`).

## Pencatatan RAT

Saat ini belum ada tabel atau model `RatMinute`. Hasil RAT
disimpan sebagai lampiran di luar aplikasi (mis. sistem arsip
internal koperasi) dan dirujuk dari `AuditLog` agar dapat
ditelusuri oleh pengurus berikutnya.
