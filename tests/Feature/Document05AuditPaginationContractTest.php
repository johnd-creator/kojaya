<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class Document05AuditPaginationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_context_preserves_explicit_domain_actor(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $other = User::factory()->create();
        $correlationId = '4e3f7a5d-6f14-4ba1-8b40-3b5f6c2b6710';

        app(AuditLogService::class)->log(
            'document05.context.test',
            'test',
            null,
            ['new' => ['actor_id' => $actor->id]],
            AuditContext::forActor($actor, 'queue', correlationId: $correlationId),
        );

        $audit = AuditLog::query()->where('action', 'document05.context.test')->sole();

        $this->assertSame((string) $actor->id, (string) $audit->user_id);
        $this->assertSame((string) $organization->id, (string) $audit->organization_id);
        $this->assertSame($correlationId, $audit->correlation_id);
        $this->assertSame('queue', $audit->source);
        $this->assertNotSame((string) $other->id, (string) $audit->user_id);
    }

    public function test_audit_redaction_is_recursive_and_covers_crypto_and_transport_fields(): void
    {
        $sentinels = [
            'identity_number' => '3201234567890001',
            'nested' => [
                'NIK' => '3201234567890001',
                'gateway_payload' => ['ciphertext' => 'ciphertext-sentinel'],
                'blind_index' => 'blind-index-sentinel',
                'authorization' => 'Bearer token-sentinel',
            ],
        ];

        app(AuditLogService::class)->log('document05.redaction.test', 'test', null, ['new' => $sentinels]);

        $audit = AuditLog::query()->where('action', 'document05.redaction.test')->sole();
        $encoded = json_encode($audit->new_values, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('3201234567890001', $encoded);
        $this->assertStringNotContainsString('ciphertext-sentinel', $encoded);
        $this->assertStringNotContainsString('blind-index-sentinel', $encoded);
        $this->assertStringNotContainsString('token-sentinel', $encoded);
        $this->assertSame('[REDACTED]', $audit->new_values['identity_number']);
        $this->assertSame('[REDACTED]', $audit->new_values['nested']['gateway_payload']);
    }

    public function test_request_derived_pagination_uses_the_central_resolver(): void
    {
        $offending = [];
        $root = app_path('Http/Controllers');

        foreach ($this->phpFiles($root) as $path => $contents) {
            if (
                preg_match('/(?:\$request|request\(\))->(?:integer|input|query)\(\s*[\'\"](?:per_page|page_size|limit)\b/', $contents)
                || preg_match('/\b(?:paginate|simplePaginate|cursorPaginate|limit|take)\(\s*\$request->/', $contents)
            ) {
                $offending[] = str_replace(base_path().'/', '', $path);
            }
        }

        $this->assertSame([], $offending);
    }

    /**
     * @return iterable<string, string>
     */
    private function phpFiles(string $root): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if ($contents !== false) {
                yield $file->getPathname() => $contents;
            }
        }
    }
}
