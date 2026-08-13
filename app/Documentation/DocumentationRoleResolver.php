<?php

declare(strict_types=1);

namespace App\Documentation;

use App\Enums\RoleExperience;
use App\Models\User;
use App\Services\Authorization\PrimaryRoleResolver;

/**
 * Single source of truth for "which documentation role bucket does this
 * user belong to?".
 *
 * The documentation center does NOT use the multi-role Spatie union that
 * the rest of the application uses for nav and policy. Instead, the
 * primary (highest) role wins. Multi-role users are routed to the
 * documentation bucket that matches their *primary* role. They may still
 * read `roles: [all]` and `roles: [shared]` articles.
 *
 * The mapping is centralised in {@see self::ROLE_EXPERIENCE_TO_DOC_ROLE}
 * so that no controller, view, or test ever does substring matching
 * against a Spatie role name.
 */
final class DocumentationRoleResolver
{
    public const ROLE_ANGGOTA = 'anggota';

    public const ROLE_ADMIN_KOPERASI = 'admin_koperasi';

    public const ROLE_MANAJER_KOPERASI = 'manajer_koperasi';

    public const ROLE_PENGURUS_KOPERASI = 'pengurus_koperasi';

    public const ROLE_SYSTEM_ADMIN = 'system_admin';

    public const ROLE_ADMIN_PUSAT = 'admin_pusat';

    public const ROLE_GENERIC = 'generic';

    /**
     * All documentation role buckets the resolver itself can return.
     *
     * Note: `all` and `shared` are NOT included here — they are
     * article-level targets rather than resolver outputs. Helper
     * methods that historically checked them were unsafe because
     * they hand-rolled a separate membership check.
     */
    public const ALL_DOC_ROLES = [
        self::ROLE_ANGGOTA,
        self::ROLE_ADMIN_KOPERASI,
        self::ROLE_MANAJER_KOPERASI,
        self::ROLE_PENGURUS_KOPERASI,
        self::ROLE_SYSTEM_ADMIN,
        self::ROLE_ADMIN_PUSAT,
        self::ROLE_GENERIC,
    ];

    /**
     * Primary-role → documentation bucket. Order is significant: the
     * `PrimaryRoleResolver` already returns the highest priority
     * `RoleExperience`; we map it here to a single documentation
     * identifier.
     *
     * @var array<string, string>
     */
    private const ROLE_EXPERIENCE_TO_DOC_ROLE = [
        'system-admin' => self::ROLE_SYSTEM_ADMIN,
        'admin-pusat' => self::ROLE_ADMIN_PUSAT,
        'pengurus' => self::ROLE_PENGURUS_KOPERASI,
        'manajer' => self::ROLE_MANAJER_KOPERASI,
        'admin-koperasi' => self::ROLE_ADMIN_KOPERASI,
        'kasir' => self::ROLE_ADMIN_KOPERASI,
        'generic' => self::ROLE_GENERIC,
    ];

    /**
     * Spatie role name → documentation bucket. This is the *fallback*
     * used when the user is an Anggota (who has no entry in
     * `PrimaryRoleResolver::ROLE_EXPERIENCES`) or when the resolver
     * returns `generic`. We never fall back to this for users who
     * already resolved to a non-generic role.
     *
     * @var array<string, string>
     */
    private const SPATIE_ROLE_TO_DOC_ROLE = [
        'System Admin' => self::ROLE_SYSTEM_ADMIN,
        'Admin Pusat' => self::ROLE_ADMIN_PUSAT,
        'Pengurus Koperasi' => self::ROLE_PENGURUS_KOPERASI,
        'Manajer Koperasi' => self::ROLE_MANAJER_KOPERASI,
        'Admin Koperasi' => self::ROLE_ADMIN_KOPERASI,
        'Kasir Koperasi' => self::ROLE_ADMIN_KOPERASI,
        'Anggota' => self::ROLE_ANGGOTA,
    ];

    public function __construct(
        private readonly PrimaryRoleResolver $resolver,
    ) {}

    /**
     * Resolve the user's documentation role bucket.
     *
     * Rules:
     *  1. If the user has the `System Admin` Spatie role, the bucket
     *     is `system_admin` — they can read every article.
     *  2. Otherwise we delegate to {@see PrimaryRoleResolver} so the
     *     primary/effective role is the same authority used by the
     *     rest of the app. Map the `RoleExperience` to a
     *     documentation bucket via the central table above.
     *  3. The `Anggota` Spatie role is explicitly recognised even
     *     though the `PrimaryRoleResolver` returns `generic` for
     *     them — otherwise an Anggota would fall into the `generic`
     *     bucket and never see the member-side articles.
     *  4. Anything that does not match returns `generic`, which can
     *     only read `roles: [all]` and `roles: [shared]` articles.
     */
    public function resolve(?User $user): string
    {
        if (! $user) {
            return self::ROLE_GENERIC;
        }

        $experience = $this->resolver->resolve($user);

        if ($experience === RoleExperience::SystemAdmin) {
            return self::ROLE_SYSTEM_ADMIN;
        }

        if ($experience !== RoleExperience::Generic) {
            $bucket = self::ROLE_EXPERIENCE_TO_DOC_ROLE[$experience->value] ?? null;
            if ($bucket !== null) {
                return $bucket;
            }
        }

        // Fallback: explicit Spatie name match. The check is exact,
        // never substring, so `Site Manager` cannot leak into
        // `Manajer Koperasi`.
        foreach (self::SPATIE_ROLE_TO_DOC_ROLE as $roleName => $bucket) {
            if ($user->hasRole($roleName)) {
                return $bucket;
            }
        }

        return self::ROLE_GENERIC;
    }
}
