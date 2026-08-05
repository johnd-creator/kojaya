<?php

declare(strict_types=1);

namespace App\Documentation;

use Illuminate\Support\Str;

/**
 * Render article bodies safely.
 *
 * Implementation strategy:
 *  1. Convert Markdown to HTML using a small, deterministic converter
 *     that understands only the constructs the user guide needs:
 *     headings, paragraphs, lists, tables, code spans/blocks, inline
 *     emphasis, links, blockquotes, and image embeds.
 *  2. Sanitise the resulting HTML through a strict allow-list that
 *     strips every element and attribute not on the safe list. The
 *     sanitiser is regex-based because the corpus is curated and the
 *     Markdown dialect is constrained; this avoids pulling in a full
 *     HTML DOM library and keeps the rendering deterministic.
 *  3. Append heading anchors so the table of contents can deep-link to
 *     each section.
 */
final class ArticleRenderer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'br', 'hr',
        'ul', 'ol', 'li',
        'strong', 'em', 'code', 'pre',
        'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'a', 'img',
        'figure', 'figcaption',
        'span', 'div',
    ];

    /** @var array<string, list<string>> */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'rel', 'target', 'id'],
        'img' => ['src', 'alt', 'title', 'loading', 'width', 'height'],
        'th' => ['scope', 'id'],
        'td' => ['id'],
        'h1' => ['id'], 'h2' => ['id'], 'h3' => ['id'],
        'h4' => ['id'], 'h5' => ['id'], 'h6' => ['id'],
        'pre' => ['data-language'],
        'code' => ['data-language'],
        'span' => ['id'],
        'div' => ['class', 'id'],
        'figure' => ['id'],
    ];

    private const SAFE_URL_SCHEMES = ['http:', 'https:', 'mailto:', 'tel:'];

    public function render(Article $article): RenderedArticle
    {
        $body = $this->stripDangerousBlocks($article->body);
        $html = $this->markdownToHtml($body);
        $sanitised = $this->sanitise($html);
        $withAnchors = $this->ensureHeadingAnchors($sanitised, $article->slug());

        $toc = $this->extractTableOfContents($withAnchors);

        return new RenderedArticle(
            html: $withAnchors,
            tableOfContents: $toc,
        );
    }

    private function stripDangerousBlocks(string $markdown): string
    {
        $patterns = [
            '#<script\b[^>]*>.*?</script>#is',
            '#<style\b[^>]*>.*?</style>#is',
            '#<iframe\b[^>]*>.*?</iframe>#is',
            '#<object\b[^>]*>.*?</object>#is',
            '#<embed\b[^>]*>#is',
            '#<form\b[^>]*>.*?</form>#is',
        ];
        foreach ($patterns as $pattern) {
            $markdown = preg_replace($pattern, '', $markdown) ?? $markdown;
        }

        return $markdown;
    }

    private function markdownToHtml(string $markdown): string
    {
        $lines = preg_split('/\R/u', $markdown) ?: [];
        $html = '';
        $i = 0;
        $listStack = [];
        $paragraph = [];

        $flushParagraph = static function () use (&$paragraph, &$html): void {
            if ($paragraph === []) {
                return;
            }
            $text = trim(implode(' ', $paragraph));
            if ($text !== '') {
                $html .= '<p>'.self::inlineMarkdown($text).'</p>'."\n";
            }
            $paragraph = [];
        };

        $closeLists = static function () use (&$listStack, &$html): void {
            while ($listStack !== []) {
                $tag = array_pop($listStack);
                $html .= '</'.$tag.'>'."\n";
            }
        };

        while ($i < count($lines)) {
            $line = $lines[$i];
            $trimmed = trim($line);

            if ($trimmed === '') {
                $flushParagraph();
                $closeLists();
                $i++;
                continue;
            }

            if (preg_match('/^```(\S*)\s*$/', $trimmed, $m)) {
                $flushParagraph();
                $closeLists();
                $language = $m[1] !== '' ? htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') : '';
                $code = '';
                $i++;
                while ($i < count($lines) && ! preg_match('/^```\s*$/', $lines[$i])) {
                    $code .= $lines[$i]."\n";
                    $i++;
                }
                $i++;
                $attr = $language !== '' ? ' data-language="'.$language.'"' : '';
                $html .= '<pre'.$attr.'><code>'.htmlspecialchars(rtrim($code, "\n"), ENT_QUOTES, 'UTF-8').'</code></pre>'."\n";
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                $closeLists();
                $level = strlen($m[1]);
                $content = self::inlineMarkdown($m[2]);
                $html .= "<h{$level}>{$content}</h{$level}>\n";
                $i++;
                continue;
            }

            if (preg_match('/^(?:\*\s*){3,}$|(?:-\s*){3,}$|(?:_\s*){3,}$/', $trimmed)) {
                $flushParagraph();
                $closeLists();
                $html .= '<hr />'."\n";
                $i++;
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                $closeLists();
                $html .= '<blockquote><p>'.self::inlineMarkdown($m[1]).'</p></blockquote>'."\n";
                $i++;
                continue;
            }

            if (str_contains($trimmed, '|') && $i + 1 < count($lines) && preg_match('/^\s*\|?[\s:|-]+\|?\s*$/', $lines[$i + 1])) {
                $flushParagraph();
                $closeLists();
                $html .= $this->renderTable($lines[$i], $lines[$i + 1], $i + 2 < count($lines) ? $lines[$i + 2] : null);
                $i += 3;
                continue;
            }

            if (preg_match('/^[-*+]\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                if (end($listStack) !== 'ul') {
                    $closeLists();
                    $html .= '<ul>'."\n";
                    $listStack[] = 'ul';
                }
                $html .= '<li>'.self::inlineMarkdown($m[1]).'</li>'."\n";
                $i++;
                continue;
            }

            if (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                if (end($listStack) !== 'ol') {
                    $closeLists();
                    $html .= '<ol>'."\n";
                    $listStack[] = 'ol';
                }
                $html .= '<li>'.self::inlineMarkdown($m[1]).'</li>'."\n";
                $i++;
                continue;
            }

            $paragraph[] = $trimmed;
            $i++;
        }

        $flushParagraph();
        $closeLists();

        return $html;
    }

    private function renderTable(string $headerLine, string $separatorLine, ?string $bodyFirstLine): string
    {
        $header = $this->splitTableRow($headerLine);
        $rows = [];
        if ($bodyFirstLine !== null) {
            foreach (preg_split('/\R/u', $bodyFirstLine) ?: [] as $line) {
                if (trim($line) === '' || ! str_contains($line, '|')) {
                    continue;
                }
                $rows[] = $this->splitTableRow($line);
            }
        }

        $html = '<table>'."\n";
        $html .= '<thead><tr>';
        foreach ($header as $cell) {
            $html .= '<th scope="col">'.self::inlineMarkdown($cell).'</th>';
        }
        $html .= '</tr></thead>'."\n";
        if ($rows !== []) {
            $html .= '<tbody>'."\n";
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>'.self::inlineMarkdown($cell).'</td>';
                }
                $html .= '</tr>'."\n";
            }
            $html .= '</tbody>'."\n";
        }
        $html .= '</table>'."\n";

        return $html;
    }

    /**
     * @return list<string>
     */
    private function splitTableRow(string $line): array
    {
        $line = trim($line);
        $line = ltrim($line, '|');
        $line = rtrim($line, '|');
        $cells = array_map('trim', explode('|', $line));

        return array_values($cells);
    }

    private static function inlineMarkdown(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $text = preg_replace_callback(
            '/`([^`\n]+)`/',
            static fn (array $m): string => '<code>'.$m[1].'</code>',
            $text,
        ) ?? $text;

        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)\s]+)(?:\s+"([^"]*)")?\)/',
            static function (array $m): string {
                $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                $src = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                $title = $m[3] ?? '';

                return '<figure><img src="'.$src.'" alt="'.$alt.'" loading="lazy" />'
                    .($title !== '' ? '<figcaption>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</figcaption>' : '')
                    .'</figure>';
            },
            $text,
        ) ?? $text;

        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)(?:\s+"([^"]*)")?\)/',
            static function (array $m): string {
                $label = $m[1];
                $href = $m[2];
                $title = $m[3] ?? '';

                return '<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'"'
                    .($title !== '' ? ' title="'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'"' : '')
                    .' rel="noopener noreferrer">'.$label.'</a>';
            },
            $text,
        ) ?? $text;

        $text = preg_replace('/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!^)\*([^*\n]+)\*(?!$)/', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/__([^_\n]+)__/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!^)_([^_\n]+)_(?!$)/', '<em>$1</em>', $text) ?? $text;

        return $text;
    }

    private function sanitise(string $html): string
    {
        // Strip any <script> or <style> blocks entirely (case-insensitive).
        // The text content is removed, not escaped, so attackers cannot
        // ship JS payloads hidden inside <script> bodies.
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;

        // Strip iframe / object / embed / form (defence in depth).
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html) ?? $html;
        $html = preg_replace('#<object\b[^>]*>.*?</object>#is', '', $html) ?? $html;
        $html = preg_replace('#<embed\b[^>]*>#is', '', $html) ?? $html;
        $html = preg_replace('#<form\b[^>]*>.*?</form>#is', '', $html) ?? $html;

        // Strip inline event handlers from every tag (on*="..." or on*='...').
        $html = preg_replace('#\s+on\w+\s*=\s*"[^"]*"#i', '', $html) ?? $html;
        $html = preg_replace("#\s+on\w+\s*=\s*'[^']*'#i", '', $html) ?? $html;
        $html = preg_replace('#\s+on\w+\s*=\s*[^\s>]+#i', '', $html) ?? $html;

        // Strip javascript: and data: URIs in href/src (case-insensitive).
        $html = preg_replace_callback(
            '/(href|src)\s*=\s*"([^"]*)"/i',
            function (array $m): string {
                $attr = strtolower($m[1]);
                $value = $m[2];
                if (! $this->isSafeUrl($value)) {
                    return $attr.'=""';
                }

                return $m[0];
            },
            $html,
        ) ?? $html;
        $html = preg_replace_callback(
            "/(href|src)\s*=\s*'([^']*)'/i",
            function (array $m): string {
                $attr = strtolower($m[1]);
                $value = $m[2];
                if (! $this->isSafeUrl($value)) {
                    return $attr."=''";
                }

                return $m[0];
            },
            $html,
        ) ?? $html;

        // Remove every tag that is not on the allow-list. This intentionally
        // discards <iframe>, <embed>, <object>, <form>, <input>, etc.
        $html = preg_replace_callback(
            '/<\/?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/',
            function (array $m): string {
                $tag = strtolower($m[1]);
                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    return '';
                }

                // Strip attributes not allowed for this tag.
                $self = $this;
                $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
                $rebuilt = preg_replace_callback(
                    '/\s+([a-zA-Z][a-zA-Z0-9_-]*)\s*=\s*"([^"]*)"/',
                    static function (array $attr) use ($allowed, $self): string {
                        $name = strtolower($attr[1]);
                        if (! in_array($name, $allowed, true)) {
                            return '';
                        }
                        if (($name === 'href' || $name === 'src') && ! $self->isSafeUrl($attr[2])) {
                            return '';
                        }

                        return ' '.$name.'="'.htmlspecialchars($attr[2], ENT_QUOTES, 'UTF-8').'"';
                    },
                    $m[0],
                ) ?? $m[0];
                $rebuilt = preg_replace_callback(
                    "/\s+([a-zA-Z][a-zA-Z0-9_-]*)\s*=\s*'([^']*)'/",
                    static function (array $attr) use ($allowed, $self): string {
                        $name = strtolower($attr[1]);
                        if (! in_array($name, $allowed, true)) {
                            return '';
                        }
                        if (($name === 'href' || $name === 'src') && ! $self->isSafeUrl($attr[2])) {
                            return '';
                        }

                        return ' '.$name.'="'.htmlspecialchars($attr[2], ENT_QUOTES, 'UTF-8').'"';
                    },
                    $rebuilt,
                ) ?? $rebuilt;

                return $rebuilt;
            },
            $html,
        ) ?? $html;

        return trim($html);
    }

    private function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return true;
        }
        foreach (self::SAFE_URL_SCHEMES as $scheme) {
            if (str_starts_with(strtolower($url), $scheme)) {
                return true;
            }
        }

        return false;
    }

    private function ensureHeadingAnchors(string $html, string $articleSlug): string
    {
        return preg_replace_callback(
            '/<(h[1-6])([^>]*)>(.*?)<\/\\1>/s',
            function (array $m) use ($articleSlug): string {
                $tag = $m[1];
                $attrs = $m[2];
                $inner = $m[3];
                if (str_contains($attrs, ' id=')) {
                    return $m[0];
                }
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES, 'UTF-8'));
                $slug = Str::slug($text);
                if ($slug === '') {
                    return $m[0];
                }
                $id = $articleSlug.'-'.$slug;

                return "<{$tag} id=\"{$id}\"{$attrs}>{$inner}</{$tag}>";
            },
            $html,
        ) ?? $html;
    }

    /**
     * @return list<array{level: int, id: string, text: string}>
     */
    private function extractTableOfContents(string $html): array
    {
        $toc = [];
        if (! preg_match_all('/<(h[1-6])[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/\\1>/s', $html, $matches, PREG_SET_ORDER)) {
            return $toc;
        }
        foreach ($matches as $m) {
            $level = (int) substr($m[1], 1);
            $id = $m[2];
            $text = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES, 'UTF-8'));
            if ($text === '' || $id === '' || $level > 3) {
                continue;
            }
            $toc[] = [
                'level' => $level,
                'id' => $id,
                'text' => $text,
            ];
        }

        return $toc;
    }
}
