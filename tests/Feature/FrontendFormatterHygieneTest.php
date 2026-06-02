<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FrontendFormatterHygieneTest extends TestCase
{
    /**
     * Folders allowed to host the canonical Rupiah / id-ID formatter.
     */
    private const ALLOWED_PREFIXES = [
        'lib/formatters.ts',
        'lib/formatters.spec.ts',
    ];

    public function test_no_local_rupiah_formatter_in_pages_or_components(): void
    {
        $offending = [];

        foreach ($this->iterateFrontendFiles() as $relativePath => $contents) {
            // Skip the canonical formatter itself.
            foreach (self::ALLOWED_PREFIXES as $allowed) {
                if (str_ends_with($relativePath, $allowed)) {
                    continue 2;
                }
            }

            $hits = [];

            // Inline `formatRupiah` helper functions.
            if (preg_match('/function\s+formatRupiah\b/', $contents)) {
                $hits[] = 'formatRupiah() function';
            }

            // Inline currency template literal: `Rp ${value.toLocaleString("id-ID")}`
            if (preg_match('/Rp\s*\$\{[^}]+toLocaleString\(\s*[\'"]id-ID[\'"]/', $contents)) {
                $hits[] = 'inline `Rp ${...toLocaleString("id-ID")}`';
            }

            // Inline Intl.NumberFormat for id-ID outside lib/formatters.ts
            if (preg_match('/new\s+Intl\.NumberFormat\(\s*[\'"]id-ID[\'"]/', $contents)) {
                $hits[] = 'new Intl.NumberFormat("id-ID")';
            }

            if ($hits !== []) {
                $offending[$relativePath] = $hits;
            }
        }

        if ($offending !== []) {
            $message = "Use `formatCurrency`/`formatNumber` from `@/lib/formatters` instead of inline formatters:\n";

            foreach ($offending as $file => $hits) {
                $message .= "  - {$file}: ".implode(', ', $hits)."\n";
            }

            $this->fail($message);
        }

        $this->assertTrue(true);
    }

    /**
     * @return iterable<string, string>
     */
    private function iterateFrontendFiles(): iterable
    {
        $root = realpath(__DIR__.'/../../resources/js') ?: dirname(__DIR__, 2).'/resources/js';

        if (! is_dir($root)) {
            $this->markTestSkipped('Frontend source directory not found.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());

            if (! in_array($extension, ['vue', 'ts', 'tsx', 'js'], true)) {
                continue;
            }

            $relative = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                continue;
            }

            yield $relative => $contents;
        }
    }
}
