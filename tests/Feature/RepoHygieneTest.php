<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

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
        $offending = [];

        foreach ($this->trackedFiles() as $relativePath) {
            if ($this->isExcluded($relativePath)) {
                continue;
            }

            $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

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

    public function test_root_probe_artifacts_are_not_tracked(): void
    {
        $offending = [];

        foreach ($this->trackedFiles() as $path) {
            if (! file_exists($this->repoRoot().DIRECTORY_SEPARATOR.$path)) {
                continue;
            }

            if (preg_match('/^(grep-count\.txt|harga\.md|presentasi\.html|s15-.*\.txt|rencana-pengembangan-sikopin\.html)$/', $path)) {
                $offending[] = $path;
            }
        }

        $this->assertSame(
            [],
            $offending,
            "Root probe/presentation artifacts should live under docs/internal or stay untracked:\n - ".implode("\n - ", $offending),
        );
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

    /**
     * @return list<string>
     */
    private function trackedFiles(): array
    {
        $output = shell_exec('git -C '.escapeshellarg($this->repoRoot()).' ls-files') ?: '';

        return array_values(array_filter(explode("\n", trim($output))));
    }
}
