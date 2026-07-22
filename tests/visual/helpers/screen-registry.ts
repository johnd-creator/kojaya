import type { AuditScreenDefinition } from "./audit-manifest";

export const screenRegistry: AuditScreenDefinition[] = [
    { id: "dashboard-default", module: "dashboard", screen: "dashboard", route: "/dashboard", role: "Pengurus Koperasi", state: "default", goal: "Memahami ringkasan operasional koperasi", primary_actions: ["Melihat KPI", "Membuka modul"], risk_level: "informational" },
    { id: "members-index-default", module: "members", screen: "index", route: "/cooperative/members", role: "Pengurus Koperasi", state: "default", goal: "Menemukan dan meninjau anggota koperasi", primary_actions: ["Mencari anggota", "Membuka detail anggota"], risk_level: "operational" },
    { id: "store-credit-index-default", module: "store-credit", screen: "index", route: "/cooperative/store-credit", role: "Pengurus Koperasi", state: "default", goal: "Mencari, memfilter, dan membuka akun Saldo Toko anggota", primary_actions: ["Mencari anggota", "Memfilter saldo", "Membuka akun", "Melihat detail akun"], risk_level: "transactional" },
    { id: "store-credit-index-empty", module: "store-credit", screen: "index", route: "/cooperative/store-credit?audit_state=empty", role: "Pengurus Koperasi", state: "empty", goal: "Memahami keadaan ketika belum ada akun Saldo Toko", primary_actions: ["Membuka akun"], risk_level: "operational" },
    { id: "store-credit-index-search-results", module: "store-credit", screen: "index", route: "/cooperative/store-credit?q=UI%20Audit", role: "Pengurus Koperasi", state: "search-results", goal: "Menilai hasil pencarian anggota Saldo Toko", primary_actions: ["Mencari anggota", "Membuka detail anggota"], risk_level: "operational" },
    { id: "store-credit-index-no-results", module: "store-credit", screen: "index", route: "/cooperative/store-credit?q=tidak-ada-hasil", role: "Pengurus Koperasi", state: "no-results", goal: "Memberi umpan balik saat pencarian tidak menemukan hasil", primary_actions: ["Mengubah pencarian"], risk_level: "operational" },
    { id: "store-credit-index-open-account-dialog", module: "store-credit", screen: "index", route: "/cooperative/store-credit", role: "Pengurus Koperasi", state: "open-account-dialog", goal: "Membuka akun Saldo Toko anggota", primary_actions: ["Memilih anggota", "Mengisi limit", "Membuka akun"], risk_level: "transactional" },
    { id: "store-credit-index-validation-error", module: "store-credit", screen: "index", route: "/cooperative/store-credit", role: "Pengurus Koperasi", state: "validation-error", goal: "Memahami koreksi yang diperlukan pada form buka akun", primary_actions: ["Memperbaiki validasi"], risk_level: "transactional" },
    { id: "store-credit-show-positive-balance", module: "store-credit", screen: "show", route: "/cooperative/store-credit/{positive}", role: "Pengurus Koperasi", state: "positive-balance", goal: "Meninjau saldo positif dan riwayat akun anggota", primary_actions: ["Melihat ledger", "Posting setoran", "Mengubah limit"], risk_level: "transactional" },
    { id: "store-credit-show-negative-balance", module: "store-credit", screen: "show", route: "/cooperative/store-credit/{negative}", role: "Pengurus Koperasi", state: "negative-balance", goal: "Menilai akun dengan saldo negatif dan utang anggota", primary_actions: ["Melihat saldo", "Meninjau ledger"], risk_level: "transactional" },
    { id: "store-credit-show-suspended", module: "store-credit", screen: "show", route: "/cooperative/store-credit/{suspended}", role: "Pengurus Koperasi", state: "suspended", goal: "Memahami status akun yang ditangguhkan", primary_actions: ["Melihat status", "Mengaktifkan kembali akun"], risk_level: "transactional" },
    { id: "store-credit-show-empty-ledger", module: "store-credit", screen: "show", route: "/cooperative/store-credit/{empty-ledger}", role: "Pengurus Koperasi", state: "empty-ledger", goal: "Memberi umpan balik ketika akun belum mempunyai ledger", primary_actions: ["Membuka setoran"], risk_level: "transactional" },
    { id: "store-credit-show-with-ledger", module: "store-credit", screen: "show", route: "/cooperative/store-credit/{positive}", role: "Pengurus Koperasi", state: "with-ledger", goal: "Meninjau detail transaksi akun Saldo Toko", primary_actions: ["Membaca ledger", "Menilai catatan transaksi"], risk_level: "transactional" },
    { id: "store-credit-report-local", module: "store-credit", screen: "report", route: "/cooperative/store-credit-report", role: "Pengurus Koperasi", state: "local", goal: "Memantau ringkasan saldo toko pada organisasi pengguna", primary_actions: ["Menilai kewajiban", "Menilai piutang"], risk_level: "operational" },
    { id: "store-credit-report-global", module: "store-credit", screen: "report", route: "/cooperative/store-credit-report", role: "System Admin", state: "global", goal: "Memantau ringkasan saldo toko lintas organisasi", primary_actions: ["Menilai agregat koperasi", "Membandingkan risiko"], risk_level: "operational" },
    { id: "store-credit-transfers-pending", module: "store-credit", screen: "transfers", route: "/cooperative/store-credit-transfers?status=pending", role: "Pengurus Koperasi", state: "pending", goal: "Memverifikasi setoran transfer yang menunggu", primary_actions: ["Melihat bukti", "Menyetujui transfer", "Menolak transfer"], risk_level: "transactional" },
    { id: "store-credit-transfers-empty", module: "store-credit", screen: "transfers", route: "/cooperative/store-credit-transfers?audit_state=empty", role: "Pengurus Koperasi", state: "empty", goal: "Memahami keadaan ketika tidak ada transfer menunggu", primary_actions: ["Meninjau status transfer"], risk_level: "operational" },
    { id: "pos-register-default", module: "pos", screen: "register", route: "/cooperative/pos", role: "Pengurus Koperasi", state: "default", goal: "Memproses transaksi penjualan koperasi", primary_actions: ["Mencari produk", "Menambah produk", "Memilih metode pembayaran"], risk_level: "transactional" },
    { id: "profile-default", module: "profile", screen: "profile", route: "/settings/profile", role: "Pengurus Koperasi", state: "default", goal: "Meninjau dan memperbarui profil pengguna", primary_actions: ["Mengubah nama", "Mengubah email"], risk_level: "operational" },
];

export function screen(id: string): AuditScreenDefinition {
    const definition = screenRegistry.find((item) => item.id === id);
    if (!definition) {
        throw new Error(`Unknown UI audit screen: ${id}`);
    }

    return definition;
}

export function assertUniqueScreenIds(): void {
    const ids = screenRegistry.map((item) => item.id);
    if (new Set(ids).size !== ids.length) {
        throw new Error("UI audit screen registry contains duplicate IDs.");
    }
}
