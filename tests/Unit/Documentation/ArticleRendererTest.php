<?php

declare(strict_types=1);

namespace Tests\Unit\Documentation;

use App\Documentation\Article;
use App\Documentation\ArticleFrontmatter;
use App\Documentation\ArticleRenderer;
use PHPUnit\Framework\TestCase;

class ArticleRendererTest extends TestCase
{
    public function test_renders_headings_paragraphs_and_lists(): void
    {
        $rendered = $this->render("# Title\n\nParagraph.\n\n- one\n- two\n");

        $this->assertStringContainsString('Title', $rendered->html);
        $this->assertStringContainsString('<h1', $rendered->html);
        $this->assertStringContainsString('<p>Paragraph.</p>', $rendered->html);
        $this->assertStringContainsString('<ul>', $rendered->html);
        $this->assertStringContainsString('<li>one</li>', $rendered->html);
    }

    public function test_renders_code_block_escaped(): void
    {
        $rendered = $this->render("```php\n<?php echo 'hi'; ?>\n```\n");

        $this->assertStringContainsString('<pre', $rendered->html);
        $this->assertStringContainsString('&lt;?php', $rendered->html);
        $this->assertStringNotContainsString('<?php', $rendered->html);
    }

    public function test_strips_inline_event_handlers(): void
    {
        $rendered = $this->render('Click [me](javascript:alert(1) "ok")');

        $this->assertStringNotContainsString('javascript:', $rendered->html);
        $this->assertStringNotContainsString('onerror', $rendered->html);
    }

    public function test_strips_script_tags(): void
    {
        $rendered = $this->render("Hello\n\n<script>alert(1)</script>\n");

        $this->assertStringNotContainsString('<script>', $rendered->html);
        $this->assertStringNotContainsString('alert(1)', $rendered->html);
    }

    public function test_extracts_table_of_contents(): void
    {
        $rendered = $this->render("# Top\n\n## Sub 1\n\ntext\n\n## Sub 2\n");

        $ids = array_column($rendered->tableOfContents, 'id');
        $this->assertNotEmpty($ids);
        $this->assertSame('valid-article-top', $ids[0]);
    }

    public function test_safe_link_schemes_are_preserved(): void
    {
        $rendered = $this->render('Visit [the site](https://example.com) or [/docs](/docs) or [a fragment](#section).');

        $this->assertStringContainsString('https://example.com', $rendered->html);
        $this->assertStringContainsString('href="/docs"', $rendered->html);
        $this->assertStringContainsString('href="#section"', $rendered->html);
    }

    private function render(string $body): \App\Documentation\RenderedArticle
    {
        $article = new Article(
            frontmatter: new ArticleFrontmatter(
                title: 'T',
                slug: 'valid-article',
                roles: ['all'],
                permissions: [],
                permission_mode: 'all',
                module: 'test',
                route_names: [],
                risk_level: 'low',
                screenshot_entries: [],
                related_articles: [],
                last_reviewed_commit: '20c86960',
                status: 'published',
                summary: 's',
                category: 'C',
                sort_order: 1,
            ),
            body: $body,
            relativePath: 'test.md',
        );

        return (new ArticleRenderer)->render($article);
    }
}
