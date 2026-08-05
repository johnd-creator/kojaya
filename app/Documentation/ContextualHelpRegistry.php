<?php

declare(strict_types=1);

namespace App\Documentation;

/**
 * Central registry of contextual help mappings.
 *
 * The mapping shape is fixed in the in-app user guide contract:
 *
 *   route name  →  documentation slug  →  role  →  permission  →  screenshot state
 *
 * Every entry is sourced from the actual route table. Entries are
 * loaded statically from {@see self::DEFAULT_MAP} for now; once the
 * validation surface proves out the contract, the registry can be
 * wired to read from a JSON file under `resources/docs/user-guide/`.
 *
 * The registry is the single place a developer touches when adding a
 * "Lihat Panduan" link to a page. The frontend reads from this same
 * map through the controller's `contextualHelp` prop, so the same
 * source of truth powers the shared layout's button and the
 * dedicated `/documentation` page.
 */
final class ContextualHelpRegistry
{
    /**
     * @var list<array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     * }>
     */
    private const DEFAULT_MAP = [
        [
            'route' => 'member.dashboard',
            'slug' => 'anggota-portal-overview',
            'role' => 'anggota',
            'screenshot_state' => 'default',
            'label' => 'Portal Anggota',
        ],
        [
            'route' => 'member.loans',
            'slug' => 'anggota-loan-flow',
            'role' => 'anggota',
            'screenshot_state' => 'default',
            'label' => 'Pinjaman Anggota',
        ],
        [
            'route' => 'member.payments.intent',
            'slug' => 'anggota-payment-flow',
            'role' => 'anggota',
            'screenshot_state' => 'default',
            'label' => 'Pembayaran Iuran',
        ],
        [
            'route' => 'cooperative.operator.dashboard',
            'slug' => 'admin-koperasi-operational-dashboard',
            'role' => 'admin_koperasi',
            'screenshot_state' => 'default',
            'label' => 'Dashboard Admin Koperasi',
        ],
        [
            'route' => 'cooperative.loan-types.index',
            'slug' => 'admin-koperasi-loan-types',
            'role' => 'admin_koperasi',
            'permission' => 'manage_cooperative_loan_types',
            'screenshot_state' => 'default',
            'label' => 'Jenis Pinjaman',
        ],
        [
            'route' => 'cooperative.pos.index',
            'slug' => 'admin-koperasi-pos-inventory',
            'role' => 'admin_koperasi',
            'permission' => 'access_cooperative_pos',
            'screenshot_state' => 'default',
            'label' => 'Operasional POS',
        ],
        [
            'route' => 'cooperative.payments.index',
            'slug' => 'admin-koperasi-payment-queue',
            'role' => 'admin_koperasi',
            'permission' => 'manage_cooperative_payment',
            'screenshot_state' => 'default',
            'label' => 'Antrean Pembayaran',
        ],
        [
            'route' => 'cooperative.loans.index',
            'slug' => 'manajer-loan-review',
            'role' => 'manajer_koperasi',
            'permission' => 'review_cooperative_loan',
            'screenshot_state' => 'manager-review',
            'label' => 'Tinjauan Pinjaman Manajer',
        ],
        [
            'route' => 'cooperative.shu.index',
            'slug' => 'manajer-financial-monitoring',
            'role' => 'manajer_koperasi',
            'permission' => 'view_cooperative_report',
            'screenshot_state' => 'default',
            'label' => 'Pemantauan Keuangan',
        ],
        [
            'route' => 'cooperative.loans.approve',
            'slug' => 'pengurus-loan-approval',
            'role' => 'pengurus_koperasi',
            'permission' => 'approve_cooperative_loan',
            'screenshot_state' => 'chairman-approval',
            'label' => 'Persetujuan Akhir Pinjaman',
        ],
        [
            'route' => 'cooperative.shu.index',
            'slug' => 'pengurus-shu-and-governance',
            'role' => 'pengurus_koperasi',
            'permission' => 'manage_cooperative_shu',
            'screenshot_state' => 'default',
            'label' => 'SHU dan Tata Kelola',
        ],
        [
            'route' => 'audit-logs',
            'slug' => 'pengurus-shu-and-governance',
            'role' => 'pengurus_koperasi',
            'permission' => 'view_audit_logs',
            'screenshot_state' => 'default',
            'label' => 'Audit Internal',
        ],
    ];

    /**
     * @return list<array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     * }>
     */
    public function all(): array
    {
        return self::DEFAULT_MAP;
    }

    /**
     * @return list<array{
     *     route: string,
     *     slug: string,
     *     role: string,
     *     permission?: string,
     *     screenshot_state: string,
     *     label: string,
     * }>
     */
    public function forRole(string $role): array
    {
        return array_values(array_filter(
            self::DEFAULT_MAP,
            static fn (array $entry): bool => $entry['role'] === $role || $entry['role'] === 'all',
        ));
    }

    public function forRoute(string $routeName): ?array
    {
        foreach (self::DEFAULT_MAP as $entry) {
            if ($entry['route'] === $routeName) {
                return $entry;
            }
        }

        return null;
    }
}
