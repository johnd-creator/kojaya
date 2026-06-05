<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotentWrite
{
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        if (! $idempotencyKey) {
            return $next($request);
        }

        if (! preg_match('/^[A-Za-z0-9:_-]{8,128}$/', $idempotencyKey)) {
            return ApiResponse::error('Idempotency-Key tidak valid.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cacheKey = $this->cacheKey($request, $idempotencyKey);
        $fingerprint = $this->fingerprint($request);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            if (($cached['fingerprint'] ?? null) !== $fingerprint) {
                return ApiResponse::error(
                    'Idempotency-Key sudah digunakan untuk payload yang berbeda.',
                    Response::HTTP_CONFLICT,
                );
            }

            return $this->replay($cached);
        }

        $response = $next($request);

        if ($response instanceof JsonResponse && $response->getStatusCode() < 500) {
            Cache::put($cacheKey, [
                'fingerprint' => $fingerprint,
                'status' => $response->getStatusCode(),
                'content' => $response->getContent(),
            ], now()->addDay());
        }

        return $response;
    }

    private function cacheKey(Request $request, string $idempotencyKey): string
    {
        return 'idempotency:'.hash('sha256', implode('|', [
            $request->user()?->getAuthIdentifier() ?: 'guest',
            $request->method(),
            $request->route()?->uri() ?: $request->path(),
            $idempotencyKey,
        ]));
    }

    private function fingerprint(Request $request): string
    {
        $files = [];

        foreach ($request->allFiles() as $key => $file) {
            $files[$key] = is_array($file)
                ? array_map(fn ($item): array => $this->fileFingerprint($item), $file)
                : $this->fileFingerprint($file);
        }

        return hash('sha256', json_encode([
            'input' => $request->except(array_keys($request->allFiles())),
            'files' => $files,
        ], JSON_THROW_ON_ERROR));
    }

    private function fileFingerprint(mixed $file): array
    {
        return [
            'name' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
            'size' => method_exists($file, 'getSize') ? $file->getSize() : null,
            'mime' => method_exists($file, 'getMimeType') ? $file->getMimeType() : null,
        ];
    }

    /**
     * @param  array{status?: int, content?: string}  $cached
     */
    private function replay(array $cached): Response
    {
        $payload = json_decode((string) ($cached['content'] ?? '{}'), true);

        if (! is_array($payload)) {
            $payload = [];
        }

        return response()
            ->json($payload, (int) ($cached['status'] ?? Response::HTTP_OK))
            ->header('X-Idempotency-Replayed', 'true');
    }
}
