<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RepoHygieneTest extends TestCase
{
    /**
     * Backup/temp file extensions that should never be tracked in the repo.
     *
     * @var array<int, string>
     */
    private const FORBIDDEN_EXTENSIONS = [
        'bak',
        'backup',
        'orig',
        'old',
        'tmp',
        'temp',
        'rej',
        'swp',
    ];

    /**
     * Directories to skip during the scan (relative to repo root).
     *
     * @var array<int, string>
     */
    private const EXCLUDED_PREFIXES = [
        'node_modules',
        'vendor',
        '.git',
        'storage',
        'bootstrap/cache',
        'public/build',
    ];

    public function test_no_backup_or_temp_files_are_tracked_in_repo(): void
    {
        $repoRoot = $this->repoRoot();
        $offending = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($repoRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relativePath = ltrim(str_replace($repoRoot, '', $file->getPathname()), DIRECTORY_SEPARATOR);

            if ($this->isExcluded($relativePath)) {
                continue;
            }

            $extension = strtolower($file->getExtension());

            if (in_array($extension, self::FORBIDDEN_EXTENSIONS, true)) {
                $offending[] = $relativePath;
            }
        }

        $this->assertSame(
            [],
            $offending,
            "Found backup/temp files that should not be tracked:\n - ".implode("\n - ", $offending)
                ."\n\nDelete them or add the path to .gitignore.",
        );
    }

    public function test_gitignore_excludes_known_backup_extensions(): void
    {
        $gitignore = file_get_contents($this->repoRoot().'/.gitignore') ?: '';

        $required = ['*.bak', '*.backup', '*.old', '*.tmp', '*.temp'];

        foreach ($required as $pattern) {
            $this->assertStringContainsString(
                $pattern,
                $gitignore,
                "Expected .gitignore to contain `{$pattern}` so backup files do not slip back in.",
            );
        }
    }

    private function isExcluded(string $relativePath): bool
    {
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($relativePath, $prefix.DIRECTORY_SEPARATOR) || $relativePath === $prefix) {
                return true;
            }
        }

        return false;
    }

    private function repoRoot(): string
    {
        return realpath(__DIR__.'/../..') ?: dirname(__DIR__, 2);
    }
}
