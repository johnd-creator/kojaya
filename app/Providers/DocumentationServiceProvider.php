<?php

declare(strict_types=1);

namespace App\Providers;

use App\Documentation\ArticleAuthorizer;
use App\Documentation\ArticleRepository;
use App\Documentation\ContextualHelpRegistry;
use App\Documentation\DocumentationRoleResolver;
use App\Services\Authorization\PrimaryRoleResolver;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

final class DocumentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ArticleRepository::class, function (): ArticleRepository {
            $repository = new ArticleRepository(
                basePath: base_path('docs/user-guide'),
            );

            try {
                $store = Cache::store('array');
            } catch (\Throwable) {
                $store = null;
            }

            if ($store instanceof CacheRepository) {
                $repository->setCache($store, ttlSeconds: 3600);
            }

            return $repository;
        });

        $this->app->singleton(DocumentationRoleResolver::class, function ($app): DocumentationRoleResolver {
            return new DocumentationRoleResolver(
                $app->make(PrimaryRoleResolver::class),
            );
        });

        $this->app->singleton(ArticleAuthorizer::class, function ($app): ArticleAuthorizer {
            return new ArticleAuthorizer(
                $app->make(ArticleRepository::class),
                $app->make(DocumentationRoleResolver::class),
            );
        });

        $this->app->singleton(ContextualHelpRegistry::class, function (): ContextualHelpRegistry {
            $path = resource_path('docs/user-guide/contextual-help.json');
            if (! is_file($path)) {
                $path = base_path('resources/docs/user-guide/contextual-help.json');
            }

            return new ContextualHelpRegistry($path);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            base_path('docs/user-guide') => resource_path('docs/user-guide'),
        ], 'documentation');
    }
}
