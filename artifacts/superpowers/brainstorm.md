# Brainstorm: Project Feature Gap Analysis (PRD 4.2)

## Goal
Melakukan audit fitur manajemen proyek terhadap PRD 4.2 dan mengidentifikasi GAP fungsional yang belum diimplementasikan.

## Known context
*   **Existing Foundation**: Sudah ada modul dasar CRUD untuk `Project`, `Milestones`, `Tasks`, dan `Team`.
*   **Database state**: Skema database saat ini sangat minimalis, hanya menyimpan data statis (budget, progress %) tanpa kaitan otomatis ke modul lain.

## Feature Gap Analysis

### 4.2.1. Manpower Mobilization Tracking
| Requirement | Status | Gap Detail |
|-------------|--------|------------|
| Recruitment Pipeline | ❌ Missing | Belum ada status `RECRUITMENT`, `SCREENING`, `MCU` pada tim proyek. |
| PPE/Uniform Assignment | ❌ Missing | Tidak ada tracking Seragam/APD/Alat Kerja untuk tim yang dimobilisasi. |
| Placement Logistics | ❌ Missing | Belum ada field untuk info Transport & Akomodasi penempatan. |
| Manpower Realization | ⚠️ Partial | Ada list tim, tapi tidak ada target kuota (Planned vs Actual). |

### 4.2.2. Maintenance/Overhaul Projects
| Requirement | Status | Gap Detail |
|-------------|--------|------------|
| Gantt & CPM | ⚠️ Basic | Hanya ada parent/child task. Belum ada logic dependencies (FS, SS, dll). |
| Project Documents | ❌ Missing | Belum ada pengelolaan dokumen krusial: Sika, Permit, Blue Print. |
| Milestone Tracking | ✅ Done | Sudah ada model `ProjectMilestone` dengan progress %. |

### 4.2.3. Project Financial Tracking
| Requirement | Status | Gap Detail |
|-------------|--------|------------|
| Costing Integration | ❌ Missing | `actual_cost` masih statis. Perlu ditarik dari Labor Cost (Payroll) & Material (Inventory). |
| Progress Billing | ❌ Missing | Milestones belum terhubung ke sistem Invoicing/Billing. |
| P&L per Project | ❌ Missing | Tidak ada perhitungan otomatis Profitabilitas (Revenue - Cost). |

## Risks
*   **Data Consistency**: Jika Biaya Tenaga Kerja (Labor) dimasukkan manual, data finansial proyek akan cepat usang dan tidak akurat.
*   **Operational Risk**: Tanpa tracking perizinan (Permit/Sika) yang matang, proyek overhaul teknis berisiko tinggi terhadap safety.

## Options (2–4)

### Option 1: Financial & Billing Priority
Fokus menghubungkan Milestone ke Invoicing dan Costing ke Payroll/Inventory.
*   **Benefit**: Manajemen segera melihat P&L proyek.

### Option 2: Mobilization & Safety Priority
Fokus pada recruitment pipeline tim proyek dan manajemen dokumen perizinan (Sika/Permit).
*   **Benefit**: Memperkuat operasional lapangan dan kepatuhan HSE.

## Recommendation
**Option 2 (Mobilization & Safety)**. Sebagai perusahaan outsourcing dan pengelola pembangkit, kepastian tim (mobilisasi) dan izin kerja (Safety) adalah "core license" untuk bekerja. Finansial bisa menyusul setelah operasional lapangan terkontrol.

## Acceptance criteria
1. `ProjectTeam` ditingkatkan dengan status recruitment & checkbox PPE.
2. Modul `ProjectDocument` untuk upload Sika & Permit per proyek.
3. Dashboard PM menampilkan perbandingan "Planned Manpower" vs "Actual Onboarded".
