<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentationArticle extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentationArticleFactory> */
    use HasFactory;

    /**
     * The target role identifiers for which an article is published.
     *
     * @var list<string>
     */
    public const TARGET_ROLES = [
        'all',
        'anggota',
        'admin_koperasi',
        'manajer_koperasi',
        'pengurus_koperasi',
    ];

    /**
     * Map a Spatie role name (as stored by `HasRoles::getRoleNames()`) to the
     * corresponding target role identifier used by the documentation center.
     *
     * @param  list<string>  $roleNames
     * @return list<string>
     */
    public static function targetRolesForUser(array $roleNames): array
    {
        $targets = ['all'];

        foreach ($roleNames as $name) {
            $slug = strtolower(trim((string) $name));

            $mapped = match (true) {
                str_contains($slug, 'anggota') => 'anggota',
                str_contains($slug, 'admin koperasi') || str_contains($slug, 'admin-koperasi') => 'admin_koperasi',
                str_contains($slug, 'manajer') => 'manajer_koperasi',
                str_contains($slug, 'pengurus') => 'pengurus_koperasi',
                default => null,
            };

            if ($mapped !== null && ! in_array($mapped, $targets, true)) {
                $targets[] = $mapped;
            }
        }

        return $targets;
    }

    /**
     * Scope the query to articles visible to the given user (role + permission).
     *
     * @param  list<string>  $roleNames
     * @param  list<string>  $permissions
     */
    public function scopeVisibleTo(Builder $query, array $roleNames, array $permissions): Builder
    {
        $targets = self::targetRolesForUser($roleNames);

        return $query
            ->whereIn('target_role', $targets)
            ->whereNotNull('published_at')
            ->where(function (Builder $q) use ($permissions) {
                $q->whereNull('required_permissions');

                foreach ($permissions as $permission) {
                    $q->orWhereJsonContains('required_permissions', $permission);
                }
            })
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'category' => $this->category,
            'target_role' => $this->target_role,
            'required_permissions' => $this->required_permissions ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'required_permissions' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
