<?php

use App\Http\Middleware\EnsureApiResponseEnvelope;
use App\Http\Middleware\EnsureHealthBubbaServiceToken;
use App\Http\Middleware\EnsureWorkspaceType;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Responses\ApiResponseFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'healthbubba.service' => EnsureHealthBubbaServiceToken::class,
            'workspace.type' => EnsureWorkspaceType::class,
        ]);

        $middleware->preventRequestForgery(except: [
            'webhooks/payments/*',
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            EnsureApiResponseEnvelope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $responses = new ApiResponseFactory;

        $exceptions->render(function (Throwable $exception, Request $request) use ($responses) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return $responses->error(
                    message: $exception->getMessage(),
                    data: $exception->errors(),
                    statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $statusCode = match (true) {
                $exception instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
                $exception instanceof AuthorizationException => Response::HTTP_FORBIDDEN,
                $exception instanceof ModelNotFoundException => Response::HTTP_NOT_FOUND,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };
            $message = match ($statusCode) {
                Response::HTTP_UNAUTHORIZED => 'Unauthenticated.',
                Response::HTTP_FORBIDDEN => 'This action is unauthorized.',
                Response::HTTP_NOT_FOUND => 'The requested resource was not found.',
                Response::HTTP_TOO_MANY_REQUESTS => 'Too many requests. Please try again later.',
                Response::HTTP_INTERNAL_SERVER_ERROR => 'An unexpected error occurred.',
                default => $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : (Response::$statusTexts[$statusCode] ?? 'The request could not be completed.'),
            };

            return $responses->error(
                message: $message,
                statusCode: $statusCode,
            );
        });
    })->create();
