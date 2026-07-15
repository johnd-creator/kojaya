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
        $this->assertSame('[REDACTED]', $audit->new_values['nested']['gateway_payload']['ciphertext']);
    }

    public function test_request_derived_page_sizes_do_not_parse_per_page_outside_the_central_resolver(): void
    {
        $offending = [];
        $root = app_path('Http/Controllers');

        foreach ($this->phpFiles($root) as $path => $contents) {
            if (preg_match('/\$request->(?:integer|input|query)\(\s*[\'\"](?:per_page|page_size)/', $contents)) {
                $offending[] = str_replace(base_path().'/', '', $path);
            }
        }

        $this->assertSame([], $offending);
    }

    public function test_sensitive_api_lists_use_explicit_resource_allowlists(): void
    {
        $duesController = file_get_contents(app_path('Http/Controllers/Api/V1/CooperativeDuesApiController.php'));
        $paymentController = file_get_contents(app_path('Http/Controllers/Api/V1/CooperativePaymentApiController.php'));
        $loanController = file_get_contents(app_path('Http/Controllers/Api/V1/LoanApiController.php'));

        $this->assertIsString($duesController);
        $this->assertIsString($paymentController);
        $this->assertIsString($loanController);
        $this->assertStringContainsString('MemberInvoiceResource::collection', $duesController);
        $this->assertStringContainsString('CooperativePaymentResource', $paymentController);
        $this->assertStringContainsString('LoanResource::collection', $loanController);
        $this->assertDoesNotMatchRegularExpression('/response\(\)->json\(\s*\$query.*paginate/s', $duesController);
        $this->assertDoesNotMatchRegularExpression('/response\(\)->json\(\s*\[\s*[\'"]data[\'"]\s*=>\s*\$payment\b/s', $paymentController);
    }

    public function test_generic_member_forms_do_not_expose_account_user_directory(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Cooperative/CooperativeMemberController.php'));

        $this->assertIsString($controller);

        foreach (['create', 'edit'] as $method) {
            $methodSource = $this->methodSource($controller, $method);

            $this->assertDoesNotMatchRegularExpression('/User::query\s*\(/', $methodSource);
            $this->assertDoesNotMatchRegularExpression('/[\'"]users[\'"]\s*=>/', $methodSource);
        }
    }

    public function test_domain_operations_have_requested_completed_and_failed_lifecycle_events(): void
    {
        $sources = [
            file_get_contents(app_path('Services/Cooperative/MemberAccountLinkService.php')),
            file_get_contents(app_path('Http/Controllers/Api/V1/CooperativePaymentApiController.php')),
            file_get_contents(app_path('Services/Cooperative/MemberOrderReservationService.php')),
            file_get_contents(app_path('Services/Cooperative/LoanService.php')),
            file_get_contents(app_path('Console/Commands/BackfillMemberSensitiveData.php')),
            file_get_contents(app_path('Http/Controllers/Cooperative/CooperativeMemberController.php')),
        ];

        foreach ($sources as $source) {
            $this->assertIsString($source);
            $this->assertStringContainsString('.requested', $source);
            $this->assertStringContainsString('.completed', $source);
            $this->assertStringContainsString('.failed', $source);
        }
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

    private function methodSource(string $source, string $method): string
    {
        $pattern = '/public function '.preg_quote($method, '/').'\b.*?(?=\n\s+(?:public|protected|private) function|\z)/s';

        preg_match($pattern, $source, $matches);

        return $matches[0] ?? '';
    }
}
