<?php

declare(strict_types=1);

namespace Tests\Unit\Documentation;

use App\Documentation\ArticleFrontmatter;
use App\Documentation\InvalidArticleException;
use PHPUnit\Framework\TestCase;

class ArticleFrontmatterTest extends TestCase
{
    public function test_parses_complete_frontmatter(): void
    {
        $frontmatter = ArticleFrontmatter::fromArray($this->validPayload(), 'test.md');
        $this->assertSame('Valid Article', $frontmatter->title);
        $this->assertSame('valid-article', $frontmatter->slug);
        $this->assertSame('all', $frontmatter->permission_mode);
        $this->assertSame('low', $frontmatter->risk_level);
        $this->assertSame('published', $frontmatter->status);
        $this->assertSame(10, $frontmatter->sort_order);
    }

    public function test_rejects_missing_keys(): void
    {
        $this->expectException(InvalidArticleException::class);
        ArticleFrontmatter::fromArray([
            'title' => 'x',
        ], 'test.md');
    }

    public function test_rejects_invalid_role(): void
    {
        $payload = $this->validPayload();
        $payload['roles'] = ['not_a_role'];
        $this->expectException(InvalidArticleException::class);
        ArticleFrontmatter::fromArray($payload, 'test.md');
    }

    public function test_rejects_invalid_permission_mode(): void
    {
        $payload = $this->validPayload();
        $payload['permission_mode'] = 'sometimes';
        $this->expectException(InvalidArticleException::class);
        ArticleFrontmatter::fromArray($payload, 'test.md');
    }

    public function test_rejects_invalid_risk_level(): void
    {
        $payload = $this->validPayload();
        $payload['risk_level'] = 'extreme';
        $this->expectException(InvalidArticleException::class);
        ArticleFrontmatter::fromArray($payload, 'test.md');
    }

    public function test_rejects_invalid_status(): void
    {
        $payload = $this->validPayload();
        $payload['status'] = 'frozen';
        $this->expectException(InvalidArticleException::class);
        ArticleFrontmatter::fromArray($payload, 'test.md');
    }

    public function test_rejects_non_kebab_slug(): void
    {
        $payload = $this->validPayload();
        $payload['slug'] = 'Not-Kebab';
        $this->expectException(InvalidArticleException::class);
        ArticleFrontmatter::fromArray($payload, 'test.md');
    }

    public function test_rejects_short_commit(): void
    {
        $payload = $this->validPayload();
        $payload['last_reviewed_commit'] = 'dead';
        $this->expectException(InvalidArticleException::class);
        ArticleFrontmatter::fromArray($payload, 'test.md');
    }

    public function test_rejects_non_integer_sort_order(): void
    {
        $payload = $this->validPayload();
        $payload['sort_order'] = 'not-a-number';
        $this->expectException(InvalidArticleException::class);
        ArticleFrontmatter::fromArray($payload, 'test.md');
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'title' => 'Valid Article',
            'slug' => 'valid-article',
            'roles' => ['all'],
            'permissions' => [],
            'permission_mode' => 'all',
            'module' => 'test',
            'route_names' => [],
            'risk_level' => 'low',
            'screenshot_entries' => [],
            'related_articles' => [],
            'last_reviewed_commit' => '20c86960',
            'status' => 'published',
            'summary' => 'A summary',
            'category' => 'Test',
            'sort_order' => 10,
        ];
    }
}
