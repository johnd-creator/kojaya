<?php

namespace App\Console\Commands;

use App\Models\DocumentationArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Throwable;

class VerifyDocumentationRoutesCommand extends Command
{
    /**
     * The name and signature of the console command. Aliased as
     * `docs:audit-drift` for ergonomic CI use.
     *
     * @var string
     */
    protected $signature = 'docs:audit-drift
                            {--source=database : Where to load articles from. Use "database" (default) or "markdown".}
                            {--markdown-dir=docs/user-guide : When --source=markdown, directory to scan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that every route() reference inside documentation articles resolves to a defined route.';

    public function handle(): int
    {
        $routeNames = $this->collectRouteNames();
        if ($routeNames === []) {
            $this->error('No routes registered. Aborting.');

            return self::FAILURE;
        }

        $bodies = $this->loadBodies(
            (string) $this->option('source'),
            (string) $this->option('markdown-dir'),
        );

        if ($bodies === []) {
            $this->warn('No documentation bodies to verify.');

            return self::SUCCESS;
        }

        $missing = [];
        $total = 0;

        // Only match real `route('foo.bar')` references; ignore markdown
        // placeholders like `route('…')` and any non-ASCII characters.
        $pattern = "/route\\(\\s*['\"]([A-Za-z0-9_.\\-]+)['\"]\\s*\\)/";

        foreach ($bodies as $label => $body) {
            preg_match_all($pattern, $body, $matches);
            foreach ($matches[1] ?? [] as $referenced) {
                $total++;
                if (! array_key_exists($referenced, $routeNames)) {
                    $missing[] = sprintf('  - %s: route(%s)', $label, $referenced);
                }
            }
        }

        $this->line(sprintf('Verified %d route() references across %d source(s).', $total, count($bodies)));

        if ($missing !== []) {
            $this->error('Documentation references undefined route names:');
            foreach ($missing as $line) {
                $this->line($line);
            }

            return self::FAILURE;
        }

        $this->info('All documentation route() references are defined.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function collectRouteNames(): array
    {
        $names = [];
        try {
            foreach (Route::getRoutes() as $route) {
                $name = $route->getName();
                if (is_string($name) && $name !== '') {
                    $names[$name] = $name;
                }
            }
        } catch (Throwable $e) {
            $this->error('Failed to enumerate routes: '.$e->getMessage());
        }

        return $names;
    }

    /**
     * @return array<string, string> label => body text
     */
    private function loadBodies(string $source, string $markdownDir): array
    {
        return match ($source) {
            'markdown' => $this->loadFromMarkdown($markdownDir),
            'database' => $this->loadFromDatabase(),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function loadFromDatabase(): array
    {
        $bodies = [];

        // The `documentation_articles` table only exists after the
        // corresponding migration has run. In environments where it has not
        // been applied yet (fresh checkouts, CI before migrate, etc.) we
        // simply skip the database source — `--source=markdown` is the
        // authoritative read in that case.
        if (! \Illuminate\Support\Facades\Schema::hasTable('documentation_articles')) {
            return $bodies;
        }

        DocumentationArticle::query()
            ->whereNotNull('published_at')
            ->get(['slug', 'body_markdown'])
            ->each(function (DocumentationArticle $article) use (&$bodies): void {
                $bodies['article:'.$article->slug] = (string) $article->body_markdown;
            });

        return $bodies;
    }

    /**
     * @return array<string, string>
     */
    private function loadFromMarkdown(string $markdownDir): array
    {
        $bodies = [];
        $base = base_path($markdownDir);
        if (! is_dir($base)) {
            return $bodies;
        }

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($rii as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'md') {
                continue;
            }
            $path = $file->getPathname();
            $bodies['markdown:'.ltrim(substr($path, strlen($base)), DIRECTORY_SEPARATOR)] = (string) file_get_contents($path);
        }

        return $bodies;
    }
}
