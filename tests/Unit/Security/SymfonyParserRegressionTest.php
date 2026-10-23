<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class SymfonyParserRegressionTest extends TestCase
{
    #[DataProvider('cacheControlWhitespace')]
    public function test_request_cache_control_preserves_directives_with_long_whitespace(string $whitespace): void
    {
        $request = new Request(server: ['HTTP_CACHE_CONTROL' => 'no-cache'.$whitespace.', max-age=60']);

        self::assertTrue($request->headers->getCacheControlDirective('no-cache'));
        self::assertSame('60', $request->headers->getCacheControlDirective('max-age'));
    }

    public static function cacheControlWhitespace(): array
    {
        return ['spaces' => [str_repeat(' ', 25)], 'tabs' => [str_repeat("\t", 25)]];
    }

    public function test_ordinary_and_quoted_http_values_remain_compatible(): void
    {
        $request = new Request(server: ['HTTP_CACHE_CONTROL' => 'no-cache, max-age=60', 'HTTP_ACCEPT' => 'text/html, application/json;q=0.8']);

        self::assertTrue($request->headers->getCacheControlDirective('no-cache'));
        self::assertSame('60', $request->headers->getCacheControlDirective('max-age'));
        self::assertSame(['text/html', 'application/json'], $request->getAcceptableContentTypes());
        self::assertSame(['filename', 'invoice, copy.pdf'], HeaderUtils::split('filename="invoice, copy.pdf"', '='));
        self::assertSame('proof token', Cookie::fromString('proof=proof%20token; Path=/; HttpOnly', true)->getValue());
    }

    public function test_whitespace_remains_supported_as_a_header_separator(): void
    {
        self::assertSame(['foo', 'bar'], HeaderUtils::split('foo bar', ' '));
        self::assertSame(['foo', 'bar'], HeaderUtils::split("foo\tbar", "\t"));
    }

    #[DataProvider('unterminatedInlineCollections')]
    public function test_yaml_inline_lexer_obeys_nesting_limit_and_parser_can_be_reused(string $input): void
    {
        $parser = new Parser(8);

        try {
            $parser->parse($input);
            self::fail('Deep inline input must be rejected by the nesting guard.');
        } catch (ParseException $exception) {
            self::assertStringContainsString('Maximum nesting depth of 8 exceeded', $exception->getMessage());
        }

        self::assertSame(['roles' => ['admin', 'member']], $parser->parse('roles: [admin, member]'));
    }

    public static function unterminatedInlineCollections(): array
    {
        return [
            'sequence' => [str_repeat('[', 9)],
            'mapping' => [str_repeat('{', 9)],
            'mixed' => [str_repeat('[{a: ', 5)],
        ];
    }

    public function test_balanced_yaml_at_nesting_limit_and_frontmatter_remain_supported(): void
    {
        $parser = new Parser(8);
        $expected = 'value';
        for ($depth = 0; $depth < 8; $depth++) {
            $expected = [$expected];
        }

        self::assertSame($expected, $parser->parse(str_repeat('[', 8).'value'.str_repeat(']', 8)));
        self::assertSame(['title' => 'Panduan', 'roles' => ['admin', 'member'], 'published' => true], $parser->parse("title: Panduan\nroles: [admin, member]\npublished: true"));
    }
}
