<?php

namespace App\Http\Requests\Cooperative\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesFundingIdempotency
{
    protected function prepareFundingIdempotency(): void
    {
        if ($this->hasHeader('Idempotency-Key') && ! $this->has('idempotency_key')) {
            $this->merge([
                'idempotency_key' => $this->header('Idempotency-Key'),
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function idempotencyRules(): array
    {
        return [
            'idempotency_key' => ['nullable', 'string', 'max:90', 'regex:/^[A-Za-z0-9:_-]+$/'],
        ];
    }

    protected function validateFundingIdempotency(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $headerKey = $this->header('Idempotency-Key');
            $bodyKey = $this->input('idempotency_key');

            if ($this->hasHeader('Idempotency-Key') && $this->has('idempotency_key')) {
                if (is_string($bodyKey) && is_string($headerKey) && $headerKey !== $bodyKey) {
                    $validator->errors()->add(
                        'idempotency_key',
                        'Idempotency key pada header dan body tidak cocok.'
                    );
                }
            }

            if ($this->hasHeader('Idempotency-Key')) {
                if (is_string($headerKey) && strlen($headerKey) > 90) {
                    $validator->errors()->add(
                        'idempotency_key',
                        'Idempotency key pada header tidak boleh lebih dari 90 karakter.'
                    );
                }
            }
        });
    }

    public function resolvedIdempotencyKey(): ?string
    {
        $headerKey = $this->header('Idempotency-Key');
        if (is_string($headerKey) && $headerKey !== '') {
            return $headerKey;
        }

        $bodyKey = $this->input('idempotency_key');
        if (is_string($bodyKey) && $bodyKey !== '') {
            return $bodyKey;
        }

        return null;
    }
}
