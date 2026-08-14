<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureHealthBubbaServiceToken
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('services.healthbubba.service_token');
        $providedToken = $request->header('X-HealthBubba-Service-Token');

        if (! is_string($configuredToken)
            || $configuredToken === ''
            || ! is_string($providedToken)
            || ! hash_equals($configuredToken, $providedToken)) {
            return new JsonResponse([
                'message' => 'The HealthBubba service token is invalid.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
