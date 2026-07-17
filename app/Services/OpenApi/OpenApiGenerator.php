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
                'version' => (string) config('app.api_contract_version', '1.0.0'),
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

        $responseSchema = $this->responseSchemaFor($method, $uri);
        if ($responseSchema !== null) {
            $item['responses']['200']['content']['application/json']['schema'] = $responseSchema;
        }

        if ($this->returnsCreatedResponse($method, $uri)) {
            $item['responses']['201'] = $item['responses']['200'];
            unset($item['responses']['200']);
        }

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
                $this->paginationParameter($uri),
            ];
        }

        if (str_ends_with($uri, '/account-link/candidates')) {
            $item['parameters'] = [
                ...($item['parameters'] ?? []),
                [
                    'name' => 'email',
                    'in' => 'query',
                    'required' => true,
                    'schema' => ['type' => 'string', 'format' => 'email'],
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
            'ApiPaginationLinks' => [
                'type' => 'object',
                'required' => ['first', 'last', 'prev', 'next'],
                'properties' => [
                    'first' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                    'last' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                    'prev' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                    'next' => ['type' => 'string', 'format' => 'uri', 'nullable' => true],
                ],
            ],
            'ApiPaginationMeta' => [
                'type' => 'object',
                'required' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total', 'path'],
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'from' => ['type' => 'integer', 'nullable' => true],
                    'last_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                    'to' => ['type' => 'integer', 'nullable' => true],
                    'total' => ['type' => 'integer'],
                    'path' => ['type' => 'string', 'format' => 'uri'],
                ],
            ],
            'CooperativeMemberResource' => [
                'type' => 'object',
                'required' => ['id', 'organization_id', 'member_no', 'no_anggota', 'name', 'email', 'phone', 'status', 'validation_status', 'joined_at', 'identity_number', 'npwp', 'no_rekening', 'nama_pemilik_rekening', 'nama_bank', 'address'],
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'organization_id' => ['type' => 'integer', 'nullable' => true],
                    'member_no' => ['type' => 'string'],
                    'no_anggota' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email', 'nullable' => true],
                    'phone' => ['type' => 'string', 'nullable' => true],
                    'status' => ['type' => 'string'],
                    'validation_status' => ['type' => 'string'],
                    'joined_at' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'identity_number' => ['type' => 'string', 'nullable' => true],
                    'npwp' => ['type' => 'string', 'nullable' => true],
                    'no_rekening' => ['type' => 'string', 'nullable' => true],
                    'nama_pemilik_rekening' => ['type' => 'string', 'nullable' => true],
                    'nama_bank' => ['type' => 'string', 'nullable' => true],
                    'address' => ['type' => 'string', 'nullable' => true],
                    'organization' => ['type' => 'object', 'nullable' => true],
                ],
            ],
            'PaginatedMemberResponse' => [
                'type' => 'object',
                'required' => ['data', 'links', 'meta', 'success'],
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/CooperativeMemberResource']],
                    'links' => ['$ref' => '#/components/schemas/ApiPaginationLinks'],
                    'meta' => ['$ref' => '#/components/schemas/ApiPaginationMeta'],
                    'success' => ['type' => 'boolean', 'example' => true],
                ],
            ],
            'MemberResponse' => [
                'type' => 'object',
                'required' => ['data', 'success'],
                'properties' => [
                    'data' => ['$ref' => '#/components/schemas/CooperativeMemberResource'],
                    'success' => ['type' => 'boolean', 'example' => true],
                ],
            ],
            'MemberInvoice' => [
                'type' => 'object',
                'required' => ['id', 'period', 'amount', 'paid_amount', 'remaining_amount', 'due_date', 'status'],
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'period' => ['type' => 'string'],
                    'amount' => ['type' => 'number'],
                    'paid_amount' => ['type' => 'number'],
                    'remaining_amount' => ['type' => 'number'],
                    'due_date' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'status' => ['type' => 'string'],
                    'contribution_type' => [
                        'type' => 'object',
                        'nullable' => true,
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'code' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'category' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'PaginatedMemberInvoiceResponse' => [
                'type' => 'object',
                'required' => ['data', 'links', 'meta', 'success'],
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/MemberInvoice']],
                    'links' => ['$ref' => '#/components/schemas/ApiPaginationLinks'],
                    'meta' => ['$ref' => '#/components/schemas/ApiPaginationMeta'],
                    'success' => ['type' => 'boolean', 'example' => true],
                ],
            ],
            'CooperativePaymentResource' => [
                'type' => 'object',
                'required' => ['id', 'member_id', 'invoice_id', 'amount', 'payment_method', 'status', 'paid_at', 'approved_at', 'approved_by', 'reference_no', 'receipt_no', 'receipt_issued_at'],
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'member_id' => ['type' => 'integer'],
                    'invoice_id' => ['type' => 'integer', 'nullable' => true],
                    'amount' => ['type' => 'number'],
                    'payment_method' => ['type' => 'string'],
                    'status' => ['type' => 'string'],
                    'paid_at' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'approved_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'approved_by' => ['type' => 'integer', 'nullable' => true],
                    'reference_no' => ['type' => 'string', 'nullable' => true],
                    'receipt_no' => ['type' => 'string', 'nullable' => true],
                    'receipt_issued_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'contribution_type' => ['type' => 'object', 'nullable' => true],
                    'invoice' => ['allOf' => [['$ref' => '#/components/schemas/MemberInvoice']], 'nullable' => true],
                    'member' => ['type' => 'object', 'nullable' => true],
                ],
            ],
            'CooperativePaymentResponse' => [
                'type' => 'object',
                'required' => ['data', 'success'],
                'properties' => [
                    'data' => ['$ref' => '#/components/schemas/CooperativePaymentResource'],
                    'success' => ['type' => 'boolean', 'example' => true],
                ],
            ],
            'BatchCooperativePaymentResponse' => [
                'type' => 'object',
                'required' => ['data', 'success'],
                'properties' => [
                    'data' => [
                        'type' => 'object',
                        'required' => ['processed_count', 'total_amount', 'payments'],
                        'properties' => [
                            'processed_count' => ['type' => 'integer'],
                            'total_amount' => ['type' => 'number'],
                            'payments' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/CooperativePaymentResource']],
                        ],
                    ],
                    'success' => ['type' => 'boolean', 'example' => true],
                ],
            ],
            'PaginatedLoanResponse' => [
                'type' => 'object',
                'required' => ['data', 'links', 'meta', 'success'],
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Loan']],
                    'links' => ['$ref' => '#/components/schemas/ApiPaginationLinks'],
                    'meta' => ['$ref' => '#/components/schemas/ApiPaginationMeta'],
                    'success' => ['type' => 'boolean', 'example' => true],
                ],
            ],
            'LoanResponse' => [
                'type' => 'object',
                'required' => ['data', 'success'],
                'properties' => [
                    'data' => ['$ref' => '#/components/schemas/Loan'],
                    'success' => ['type' => 'boolean', 'example' => true],
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
            'RotateTokenRequest' => [
                'type' => 'object',
                'properties' => [
                    'app' => [
                        'type' => 'string',
                        'enum' => ['member', 'ess', 'technician', 'admin'],
                        'description' => 'Required when the current legacy token profile is unsafe.',
                    ],
                    'device_name' => ['type' => 'string', 'nullable' => true, 'maxLength' => 255],
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
                'required' => [
                    'id',
                    'member_id',
                    'loan_type_id',
                    'principal_amount',
                    'interest_rate',
                    'admin_fee',
                    'late_fee_per_day',
                    'term_months',
                    'installment_amount',
                    'total_interest_amount',
                    'total_amount',
                    'outstanding_amount',
                    'applied_at',
                    'first_due_date',
                    'manager_reviewed_at',
                    'manager_reviewed_by',
                    'approved_at',
                    'approved_by',
                    'disbursed_at',
                    'rejected_at',
                    'status',
                    'approval_stage',
                    'reference_no',
                    'purpose',
                    'notes',
                    'rejection_reason',
                ],
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'member_id' => ['type' => 'integer'],
                    'loan_type_id' => ['type' => 'integer'],
                    'principal_amount' => ['type' => 'number'],
                    'interest_rate' => ['type' => 'number'],
                    'admin_fee' => ['type' => 'number'],
                    'late_fee_per_day' => ['type' => 'number'],
                    'term_months' => ['type' => 'integer'],
                    'installment_amount' => ['type' => 'number'],
                    'total_interest_amount' => ['type' => 'number'],
                    'total_amount' => ['type' => 'number'],
                    'outstanding_amount' => ['type' => 'number'],
                    'applied_at' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'first_due_date' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'manager_reviewed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'manager_reviewed_by' => ['type' => 'integer', 'nullable' => true],
                    'approved_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'approved_by' => ['type' => 'integer', 'nullable' => true],
                    'disbursed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'rejected_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'status' => ['type' => 'string', 'enum' => ['APPLIED', 'MANAGER_APPROVED', 'APPROVED', 'ACTIVE', 'PAID_OFF', 'REJECTED', 'DEFAULTED', 'WRITTEN_OFF']],
                    'approval_stage' => ['type' => 'string', 'nullable' => true],
                    'reference_no' => ['type' => 'string', 'nullable' => true],
                    'purpose' => ['type' => 'string', 'nullable' => true],
                    'notes' => ['type' => 'string', 'nullable' => true],
                    'rejection_reason' => ['type' => 'string', 'nullable' => true],
                    'member' => ['type' => 'object', 'nullable' => true],
                    'loan_type' => ['type' => 'object', 'nullable' => true],
                    'installments' => ['type' => 'array', 'items' => ['type' => 'object']],
                    'payments' => ['type' => 'array', 'items' => ['type' => 'object'], 'nullable' => true],
                    'approval_logs' => ['type' => 'array', 'items' => ['type' => 'object'], 'nullable' => true],
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
            ['name' => 'Cooperative', 'description' => 'API koperasi dengan granular domain abilities dan organization scope; legacy cooperative abilities hanya compatibility bertahap.'],
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
            str_ends_with($uri, 'api/token/rotate') => ['$ref' => '#/components/schemas/RotateTokenRequest'],
            str_ends_with($uri, 'api/v1/member/onboarding/steps') => ['$ref' => '#/components/schemas/MemberOnboardingStepRequest'],
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function responseSchemaFor(string $method, string $uri): ?array
    {
        $method = strtolower($method);

        return match (true) {
            $method === 'get' && $uri === 'api/v1/members' => ['$ref' => '#/components/schemas/PaginatedMemberResponse'],
            $method === 'get' && $uri === 'api/v1/members/{member}' => ['$ref' => '#/components/schemas/MemberResponse'],
            $method === 'get' && $uri === 'api/v1/loans' => ['$ref' => '#/components/schemas/PaginatedLoanResponse'],
            $method === 'get' && $uri === 'api/v1/loans/{loan}' => ['$ref' => '#/components/schemas/LoanResponse'],
            $method === 'get' && $uri === 'api/v1/dues/invoices' => ['$ref' => '#/components/schemas/PaginatedMemberInvoiceResponse'],
            $method === 'post' && $uri === 'api/v1/dues/payments/batch' => ['$ref' => '#/components/schemas/BatchCooperativePaymentResponse'],
            $method === 'post' && in_array($uri, ['api/v1/dues/payments', 'api/v1/dues/payments/{payment}/approve'], true) => ['$ref' => '#/components/schemas/CooperativePaymentResponse'],
            default => null,
        };
    }

    private function returnsCreatedResponse(string $method, string $uri): bool
    {
        return strtolower($method) === 'post'
            && in_array($uri, ['api/v1/dues/payments', 'api/v1/dues/payments/batch'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function paginationParameter(string $uri): array
    {
        if (str_ends_with($uri, '/notifications/recent')) {
            return [
                'name' => 'limit',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'default' => 5, 'minimum' => 1, 'maximum' => 10],
            ];
        }

        return [
            'name' => 'per_page',
            'in' => 'query',
            'schema' => [
                'type' => 'integer',
                'default' => 15,
                'minimum' => 1,
                'maximum' => 50,
            ],
        ];
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
