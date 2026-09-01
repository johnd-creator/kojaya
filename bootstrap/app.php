<?php

use App\Exceptions\PaymentIntentConflictException;
use App\Http\Middleware\CheckSessionOrTokenAbility;
use App\Http\Middleware\CheckTokenAbilities;
use App\Http\Middleware\CheckTokenForAnyAbility;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\EnsureIdempotentWrite;
use App\Http\Middleware\EnsureIsMember;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\MeasureResponseTime;
use App\Http\Middleware\NormalizeApiResponse;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->append(CorrelationIdMiddleware::class);

        $middleware->api(append: [
            CorrelationIdMiddleware::class,
            MeasureResponseTime::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            LogActivity::class,
        ]);

        $middleware->api(append: [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            NormalizeApiResponse::class,
        ]);

        $middleware->alias([
            'abilities' => CheckTokenAbilities::class,
            'ability' => CheckTokenForAnyAbility::class,
            'ability.dual' => CheckSessionOrTokenAbility::class,
            'idempotent' => EnsureIdempotentWrite::class,
            'member' => EnsureIsMember::class,
            'member.active' => \App\Http\Middleware\EnsureMemberFullyActive::class,
            'member.api' => \App\Http\Middleware\EnsureApiMember::class,
            'member.api.active' => \App\Http\Middleware\EnsureApiMemberIsActive::class,
            'cooperative.ability' => \App\Http\Middleware\EnsureCooperativeAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\PaymentGatewayUnavailableException $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error(
                $exception->getMessage() ?: 'Layanan pembayaran gateway sedang tidak tersedia.',
                $exception->statusCode,
                [],
                'PAYMENT_GATEWAY_UNAVAILABLE',
            );
        });

        $exceptions->render(function (PaymentIntentConflictException $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error($exception->getMessage(), 409, [], $exception->errorCode);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $exception->getMessage() ?: 'Data tidak valid.',
                $exception->status,
                $exception->errors(),
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Unauthenticated.', 401);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Resource tidak ditemukan.', 404);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $exception->getMessage() ?: 'Request gagal.',
                $exception->getStatusCode(),
            );
        });
    })->create();
