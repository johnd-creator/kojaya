# Brainstorm: Advanced Project Tracking (ERP Projects)

## Goal
Implementasi fitur Manajemen Proyek tingkat lanjut sesuai PRD 4.2, mencakup pelacakan mobilisasi tenaga kerja, operasional pengerjaan fisik, dan monitoring finansial proyek.

## Constraints
*   **Struktur Organisasi**: Harus mendukung isolasi data per unit/site.
*   **Skalabilitas**: Harus mampu menangani ribuan karyawan yang dimobilisasi secara berkala.
*   **Integrasi**: Bergantung pada modul HRM (Pegawai) dan Finance (Budgeting).

## Known context
*   **Existing Baseline**: Sudah ada model `Project`, `ProjectTask`, `ProjectMilestone`, dan `ProjectTeam` dasar.
*   **Missing Core Logic**: Belum ada status rekrutmen/mobilisasi di tim, belum ada dokumen perizinan proyek, dan belum ada integrasi biaya material dari inventory.
*   **UI Current State**: Hanya memiliki tampilan Index, Show, dan Create dasar.

## Risks
*   **Kompleksitas CPM/Gantt**: Membuat logika Critical Path Method di backend sangat kompleks; disarankan menggunakan library frontend.
*   **Akurasi Financials**: Jika data biaya tidak ditarik dari Payroll/Inventory secara akurat, profitabilitas proyek hanya berupa estimasi.

## Options (2–4)

### Option 1: Mobilization & Labor Foundation (Priority)
Fokus pada "Manpower Mobilization Tracking". Menambahkan status rekrutmen, screening, dan placement pada `ProjectTeam`.
*   **Pro**: Sangat relevan bagi bisnis outsourcing.
*   **Con**: Belum menjawab kebutuhan monitoring fisik pengerjaan mesin/overhaul.

### Option 2: Operational Monitoring (Timeline & Docs)
Fokus pada pengerjaan fisik: Gantt Chart, CPM, dan Project Documents (Sika, Permit, PPE Checklist).
*   **Pro**: Bagus untuk proyek overhaul teknis.
*   **Con**: Membutuhkan input data yang detail dari lapangan setiap hari.

### Option 3: Financial Integrity (Costing vs Billing)
Fokus pada integrasi Billing Termin (Milestones) dan Direct Cost (Labor + Material).
*   **Pro**: Memberikan visi profitabilitas yang jelas bagi manajemen.
*   **Con**: Bergantung pada modul Inventory dan Finance yang sudah stabil.

## Recommendation
**Option 1 + Milestones**. Untuk perusahaan outsourcing, aset utamanya adalah manusia. Fokus pertama adalah memastikan rekrutmen dan penempatan (mobilisasi) terpantau dengan jelas di dalam Project, kemudian dikunci dengan Milestone untuk kebutuhan penagihan (Billing).

## Acceptance criteria
1. `ProjectTeam` memiliki status flow: `RECRUITMENT` -> `SCREENING` -> `ONBOARDING` -> `PLACED`.
2. `ProjectTask` mendukung visualisasi timeline sederhana per milestones.
3. Dashboard Project Manager dapat membandingkan budget vs actual labor cost per site.
