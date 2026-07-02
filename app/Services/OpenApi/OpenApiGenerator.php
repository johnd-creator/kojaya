<?php

namespace App\Services\OpenApi;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class OpenApiGenerator
{
    /**
     * @return array<string, mixed>
     */
    public function generate(): array
    {
        $paths = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            foreach ($this->methods($route) as $method) {
                $pathKey = '/'.$route->uri();
                $pathItem = $this->buildPathItem($method, $route);
                $paths[$pathKey][strtolower($method)] = $pathItem;
            }
        }

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Kojaya API',
                'version' => '1.0.0',
                'description' => 'API untuk Kojaya mobile apps: Kojayaku (anggota koperasi), ESS (pegawai), dan Technician (teknisi lapangan).',
            ],
            'servers' => [
                [
                    'url' => config('app.url'),
                    'description' => 'Production / Staging server',
                ],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum token',
                        'description' => 'Gunakan token Sanctum dari POST /api/auth/login',
                    ],
                ],
                'schemas' => $this->buildSchemas(),
                'responses' => $this->buildResponses(),
            ],
            'tags' => $this->buildTags(),
            'paths' => $paths,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function methods(Route $route): array
    {
        return array_values(array_filter($route->methods(), fn (string $method): bool => ! in_array($method, ['HEAD'], true)));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPathItem(string $method, Route $route): array
    {
        $uri = $route->uri();
        $tag = $this->routeTag($uri);
        $middleware = $route->gatherMiddleware();

        $item = [
            'summary' => $route->getName() ?: $this->summaryFromUri($method, $uri),
            'operationId' => $this->operationId($method, $route),
            'tags' => [$tag],
            'responses' => [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/SuccessEnvelope'],
                        ],
                    ],
                ],
                '401' => ['$ref' => '#/components/responses/Unauthenticated'],
                '403' => ['$ref' => '#/components/responses/Forbidden'],
                '422' => ['$ref' => '#/components/responses/ValidationError'],
                '429' => ['description' => 'Too many requests'],
            ],
        ];

        $abilities = $this->abilities($middleware);

        if ($abilities !== []) {
            $item['x-required-abilities'] = $abilities;
        }

        $pathParameters = $this->pathParameters($uri);

        if ($pathParameters !== []) {
            $item['parameters'] = $pathParameters;
        }

        $requestSchema = $this->requestSchemaFor($method, $uri);

        if ($requestSchema !== null || $this->hasMiddlewareAbility($middleware, 'write')) {
            $item['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $requestSchema ?? ['type' => 'object'],
                    ],
                ],
            ];

            if ($this->hasFileUpload($method, $uri)) {
                $item['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => ['type' => 'string', 'format' => 'binary'],
                                ],
                            ],
                        ],
                    ],
                ];
            }
        }

        if ($this->hasPagination($uri)) {
            $item['parameters'] = [
                ...($item['parameters'] ?? []),
                [
                    'name' => 'page',
                    'in' => 'query',
                    'schema' => ['type' => 'integer', 'default' => 1],
                ],
                [
                    'name' => 'per_page',
                    'in' => 'query',
                    'schema' => ['type' => 'integer', 'default' => 15],
                ],
            ];
        }

        if ($this->isHealthOrWebhook($uri)) {
            $item['security'] = [];
        }

        if (str_ends_with($uri, '/qris-image')) {
            $item['responses']['200']['content'] = [
                'image/png' => [
                    'schema' => [
                        'type' => 'string',
                        'format' => 'binary',
                    ],
                ],
            ];
        }

        if (str_ends_with($uri, 'login')) {
            $item['security'] = [];
        }

        if (str_ends_with($uri, '/openapi.json')) {
            $item['security'] = [];
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSchemas(): array
    {
        return [
            'SuccessEnvelope' => [
                'type' => 'object',
                'required' => ['success'],
                'properties' => [
                    'success' => ['type' => 'boolean', 'example' => true],
                    'data' => ['type' => 'object'],
                    'message' => ['type' => 'string'],
                ],
            ],
            'Member' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'member_no' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['ACTIVE', 'INACTIVE', 'RESIGNED']],
                ],
            ],
            'CooperativePayment' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'amount' => ['type' => 'number'],
                    'status' => ['type' => 'string', 'enum' => ['PENDING', 'APPROVED', 'REJECTED']],
                    'payment_method' => ['type' => 'string', 'enum' => ['CASH', 'TRANSFER', 'QRIS', 'VA', 'E_WALLET']],
                    'paid_at' => ['type' => 'string', 'format' => 'date'],
                    'gateway_reference' => ['type' => 'string', 'nullable' => true],
                    'gateway_status' => ['type' => 'string', 'enum' => ['PENDING', 'PAID', 'EXPIRED', 'CANCELLED', 'FAILED']],
                    'gateway_expires_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'qr_image_url' => ['type' => 'string', 'nullable' => true],
                    'poll_after_seconds' => ['type' => 'integer', 'example' => 5],
                ],
            ],
            'CreatePaymentChargeRequest' => [
                'type' => 'object',
                'required' => ['cooperative_payment_id', 'channel'],
                'properties' => [
                    'cooperative_payment_id' => ['type' => 'integer', 'example' => 123],
                    'channel' => ['type' => 'string', 'enum' => ['QRIS'], 'example' => 'QRIS'],
                ],
            ],
            'CreateMemberBillPaymentIntentRequest' => [
                'type' => 'object',
                'required' => ['channel'],
                'properties' => [
                    'channel' => ['type' => 'string', 'enum' => ['QRIS', 'VA', 'E_WALLET', 'TRANSFER'], 'example' => 'QRIS'],
                ],
            ],
            'PaymentChargeResponse' => [
                'type' => 'object',
                'properties' => [
                    'provider' => ['type' => 'string', 'example' => 'midtrans'],
                    'reference' => ['type' => 'string', 'example' => 'KOJ-123-ABC12345'],
                    'status' => ['type' => 'string', 'enum' => ['PENDING']],
                    'channel' => ['type' => 'string', 'enum' => ['QRIS', 'VA', 'E_WALLET', 'TRANSFER']],
                    'amount' => ['type' => 'number', 'example' => 100000],
                    'checkout_url' => ['type' => 'string', 'nullable' => true],
                    'qr_image_url' => ['type' => 'string', 'nullable' => true, 'example' => '/api/v1/member/payments/99/qris-image'],
                    'expires_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'instructions' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'example' => ['bank' => 'BCA', 'va_number' => '1234567890'],
                    ],
                    'poll_after_seconds' => ['type' => 'integer', 'example' => 5],
                ],
            ],
            'PaymentGatewayWebhookRequest' => [
                'type' => 'object',
                'properties' => [
                    'order_id' => ['type' => 'string', 'example' => 'KOJ-123-ABC12345'],
                    'transaction_id' => ['type' => 'string'],
                    'status_code' => ['type' => 'string', 'example' => '200'],
                    'transaction_status' => ['type' => 'string', 'example' => 'settlement'],
                    'fraud_status' => ['type' => 'string', 'example' => 'accept'],
                    'gross_amount' => ['type' => 'number', 'example' => 100000],
                    'payment_type' => ['type' => 'string', 'example' => 'qris'],
                    'signature_key' => ['type' => 'string'],
                    'reconciliation_reference' => ['type' => 'string'],
                ],
            ],
            'RegisterDeviceTokenRequest' => [
                'type' => 'object',
                'required' => ['app', 'device_id', 'push_token'],
                'properties' => [
                    'app' => ['type' => 'string', 'enum' => ['member', 'ess', 'technician', 'admin'], 'example' => 'member'],
                    'device_id' => ['type' => 'string', 'example' => 'android-member-1'],
                    'platform' => ['type' => 'string', 'enum' => ['android', 'ios'], 'nullable' => true],
                    'push_token' => ['type' => 'string'],
                ],
            ],
            'MobileDeviceToken' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'app' => ['type' => 'string'],
                    'device_id' => ['type' => 'string'],
                    'platform' => ['type' => 'string', 'nullable' => true],
                    'last_seen_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                ],
            ],
            'MemberOnboardingStepRequest' => [
                'type' => 'object',
                'required' => ['step'],
                'properties' => [
                    'step' => ['type' => 'string', 'enum' => ['profile', 'first_savings', 'loans', 'rewards'], 'example' => 'loans'],
                ],
            ],
            'Loan' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'loan_no' => ['type' => 'string'],
                    'principal' => ['type' => 'number'],
                    'status' => ['type' => 'string', 'enum' => ['APPLIED', 'MANAGER_APPROVED', 'APPROVED', 'ACTIVE', 'PAID_OFF', 'REJECTED', 'DEFAULTED', 'WRITTEN_OFF']],
                    'loanType' => ['$ref' => '#/components/schemas/LoanType'],
                ],
            ],
            'LoanType' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'interest_rate' => ['type' => 'number'],
                    'tenor_months' => ['type' => 'integer'],
                ],
            ],
            'PaginatedResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                    'current_page' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'total' => ['type' => 'integer'],
                ],
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean', 'example' => false],
                    'message' => ['type' => 'string'],
                    'error' => ['type' => 'string'],
                    'error_code' => ['type' => 'string'],
                    'error_details' => ['type' => 'object'],
                    'request_id' => ['type' => 'string', 'nullable' => true],
                    'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponses(): array
    {
        return [
            'Unauthenticated' => [
                'description' => 'Unauthorized - token tidak valid atau expired',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'Forbidden' => [
                'description' => 'Forbidden - ability tidak mencukupi',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
            'ValidationError' => [
                'description' => 'Validation error',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, description: string}>
     */
    private function buildTags(): array
    {
        return [
            ['name' => 'Auth', 'description' => 'Login, logout, session — tanpa Sanctum token'],
            ['name' => 'Member', 'description' => 'API untuk anggota koperasi (Kojayaku mobile). Ability: member:read, member:write'],
            ['name' => 'Cooperative', 'description' => 'API untuk pengurus/kasir koperasi. Ability: cooperative:read, cooperative:write'],
            ['name' => 'ESS', 'description' => 'Employee Self Service API. Ability: ess:read, ess:write, attendance:read, attendance:write'],
            ['name' => 'Technician', 'description' => 'API untuk teknisi lapangan. Ability: work-orders:read, work-orders:write, work-orders:review'],
            ['name' => 'Integration', 'description' => 'Webhook, push token, payment charge, health monitoring'],
            ['name' => 'Reports', 'description' => 'Laporan dan compliance. Ability: reports:read, employee-documents:read'],
            ['name' => 'POS', 'description' => 'Point of Sale. Ability: pos:read, pos:write'],
            ['name' => 'OpenAPI', 'description' => 'Dokumentasi kontrak API ini'],
        ];
    }

    private function operationId(string $method, Route $route): string
    {
        return strtolower($method).'_'.str($route->uri())->replace(['/', '{', '}'], '_')->trim('_')->toString();
    }

    private function routeTag(string $uri): string
    {
        return match (true) {
            str_starts_with($uri, 'api/auth') => 'Auth',
            str_starts_with($uri, 'api/openapi') => 'OpenAPI',
            str_starts_with($uri, 'api/v1/member') => 'Member',
            str_starts_with($uri, 'api/v1/pos') => 'POS',
            str_starts_with($uri, 'api/v1/reports') => 'Reports',
            str_starts_with($uri, 'api/v1') => 'Cooperative',
            str_starts_with($uri, 'api/ess') => 'ESS',
            str_starts_with($uri, 'api/technician') => 'Technician',
            str_starts_with($uri, 'api/reports') => 'Reports',
            str_starts_with($uri, 'api/employees') => 'Reports',
            str_starts_with($uri, 'api/audit-logs') => 'Reports',
            str_starts_with($uri, 'api/payments/webhook') => 'Integration',
            str_starts_with($uri, 'api/payments') => 'Member',
            str_starts_with($uri, 'api/devices') => 'Integration',
            str_starts_with($uri, 'api/monitoring') => 'Integration',
            str_starts_with($uri, 'api/token') => 'Auth',
            str_starts_with($uri, 'api/user') => 'Auth',
            default => 'General',
        };
    }

    private function summaryFromUri(string $method, string $uri): string
    {
        $action = match (strtolower($method)) {
            'get' => 'Mengambil',
            'post' => 'Membuat',
            'put' => 'Mengupdate',
            'delete' => 'Menghapus',
            default => strtoupper($method),
        };

        $resource = str($uri)->after('api/')->after('v1/')->title();

        return "{$action} {$resource}";
    }

    /**
     * @param  array<int|string, string>  $middleware
     */
    private function abilities(array $middleware): array
    {
        return array_values(array_map(
            fn (string $middleware): string => str($middleware)->after('ability:')->toString(),
            array_filter($middleware, fn (mixed $middleware): bool => is_string($middleware) && str_starts_with($middleware, 'ability:')),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pathParameters(string $uri): array
    {
        preg_match_all('/\{([^}:]+)(?::[^}]+)?}/', $uri, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $name): array => [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => is_numeric($name) ? 'integer' : 'string'],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestSchemaFor(string $method, string $uri): ?array
    {
        if (strtolower($method) === 'get') {
            return null;
        }

        return match (true) {
            str_ends_with($uri, 'api/payments/charge') => ['$ref' => '#/components/schemas/CreatePaymentChargeRequest'],
            str_ends_with($uri, 'api/payments/webhook') => ['$ref' => '#/components/schemas/PaymentGatewayWebhookRequest'],
            str_contains($uri, 'api/v1/member/bills/') && str_ends_with($uri, '/payment-intent') => ['$ref' => '#/components/schemas/CreateMemberBillPaymentIntentRequest'],
            str_ends_with($uri, 'api/devices/push-token') => ['$ref' => '#/components/schemas/RegisterDeviceTokenRequest'],
            str_ends_with($uri, 'api/v1/member/onboarding/steps') => ['$ref' => '#/components/schemas/MemberOnboardingStepRequest'],
            default => null,
        };
    }

    /**
     * @param  array<int|string, string>  $middleware
     */
    private function hasMiddlewareAbility(array $middleware, string $operation): bool
    {
        foreach ($middleware as $m) {
            if (is_string($m) && in_array($m, ['throttle:api-write', 'ability:member:write', 'ability:cooperative:write', 'ability:ess:write', 'ability:work-orders:write'], true)) {
                return true;
            }

            if (is_string($m) && str_starts_with($m, 'ability:') && str_ends_with($m, ':'.$operation)) {
                return true;
            }
        }

        return false;
    }

    private function hasFileUpload(string $method, string $uri): bool
    {
        return str_contains($uri, '/upload') || str_contains($uri, '/proof') || str_contains($uri, '/attachments');
    }

    private function hasPagination(string $uri): bool
    {
        return str_contains($uri, '/history')
            || str_contains($uri, '/ledger')
            || str_contains($uri, '/invoices')
            || str_contains($uri, '/payments')
            || str_contains($uri, '/loans')
            || str_contains($uri, '/notifications')
            || str_contains($uri, '/members')
            || str_contains($uri, '/products')
            || str_contains($uri, '/leaves')
            || str_contains($uri, '/overtime')
            || str_contains($uri, '/reimbursements')
            || str_contains($uri, '/payslips')
            || str_contains($uri, '/compliance')
            || str_contains($uri, '/work-orders')
            || str_contains($uri, '/support-tickets');
    }

    private function isHealthOrWebhook(string $uri): bool
    {
        return str_ends_with($uri, '/webhook');
    }
}
