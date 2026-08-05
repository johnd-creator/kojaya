<?php

declare(strict_types=1);

namespace App\Documentation;

final class RenderedArticle
{
    /**
     * @param  list<array{level: int, id: string, text: string}>  $tableOfContents
     */
    public function __construct(
        public readonly string $html,
        public readonly array $tableOfContents,
    ) {}
}
