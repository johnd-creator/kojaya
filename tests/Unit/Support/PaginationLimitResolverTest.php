<?php

namespace Tests\Unit\Support;

use App\Support\PaginationLimitResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaginationLimitResolverTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: int}>
     */
    public static function pageSizeProvider(): array
    {
        return [
            'omitted' => [null, 15],
            'negative' => ['-1', 1],
            'zero' => ['0', 1],
            'minimum' => ['1', 1],
            'maximum' => ['50', 50],
            'above maximum' => ['999999', 50],
            'non numeric' => ['not-a-number', 15],
        ];
    }

    #[DataProvider('pageSizeProvider')]
    public function test_api_page_size_is_bounded_and_non_numeric_input_uses_default(?string $value, int $expected): void
    {
        $request = Request::create('/api/example', 'GET', $value === null ? [] : ['per_page' => $value]);

        $this->assertSame($expected, (new PaginationLimitResolver)->resolve($request));
    }

    public function test_documented_admin_endpoint_can_use_the_higher_bound(): void
    {
        $request = Request::create('/cooperative/dues', 'GET', ['per_page' => '100']);

        $this->assertSame(100, (new PaginationLimitResolver)->resolve($request, maximum: 100));
    }

    public function test_named_limit_parameter_uses_the_same_contract(): void
    {
        $resolver = new PaginationLimitResolver;

        $this->assertSame(5, $resolver->resolve(Request::create('/api/example'), 'limit', default: 5, maximum: 10));
        $this->assertSame(1, $resolver->resolve(Request::create('/api/example?limit=-1'), 'limit', default: 5, maximum: 10));
        $this->assertSame(10, $resolver->resolve(Request::create('/api/example?limit=999'), 'limit', default: 5, maximum: 10));
        $this->assertSame(5, $resolver->resolve(Request::create('/api/example?limit=bad'), 'limit', default: 5, maximum: 10));
        $this->assertSame(5, $resolver->resolve(Request::create('/api/example?limit[]=bad'), 'limit', default: 5, maximum: 10));
    }
}
