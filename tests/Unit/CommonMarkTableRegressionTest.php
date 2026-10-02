<?php

namespace Tests\Unit;

use Composer\InstalledVersions;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use PHPUnit\Framework\TestCase;

class CommonMarkTableRegressionTest extends TestCase
{
    public function test_pipe_free_numeric_paragraph_does_not_trigger_quadratic_table_scan(): void
    {
        $this->assertLongParagraphIsBounded('12345678');
    }

    public function test_pipe_free_punctuation_paragraph_does_not_trigger_quadratic_table_scan(): void
    {
        $this->assertLongParagraphIsBounded('?2345678');
    }

    public function test_normal_markdown_tables_and_paragraphs_remain_supported(): void
    {
        $converter = new GithubFlavoredMarkdownConverter;
        $html = (string) $converter->convert("Catatan anggota\n\n| Nama | Saldo |\n| --- | ---: |\n| Audit | 150000 |\n");

        $this->assertStringContainsString('<p>Catatan anggota</p>', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>Nama</th>', $html);
        $this->assertStringContainsString('<td>Audit</td>', $html);
        $this->assertStringContainsString('150000</td>', $html);
    }

    private function assertLongParagraphIsBounded(string $line): void
    {
        $this->assertTrue(InstalledVersions::satisfies(new \Composer\Semver\VersionParser, 'league/commonmark', '>=2.10.2'));
        $converter = new GithubFlavoredMarkdownConverter;
        $html = (string) $converter->convert(str_repeat($line."\n", 25000));

        $this->assertStringStartsWith('<p>'.$line, $html);
        $this->assertStringNotContainsString('<table>', $html);
        $this->assertSame(25000, substr_count($html, $line));
        // Enforce the patched parser version rather than wall-clock timing,
        // which is not comparable under parallel Xdebug coverage collection.
    }
}
