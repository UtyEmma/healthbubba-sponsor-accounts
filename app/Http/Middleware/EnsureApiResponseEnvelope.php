<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponseFactory;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureApiResponseEnvelope
{
    public function __construct(private ApiResponseFactory $responses) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse || $this->isEnvelope($response)) {
            return $response;
        }

        $payload = $response->getData(true);
        $payload = is_array($payload) ? $payload : [];
        $statusCode = $response->getStatusCode();
        $message = $this->message($request, $payload, $statusCode);

        unset($payload['message']);

        if ($response->isSuccessful()) {
            return $this->responses
                ->success($payload, $message, $statusCode)
                ->withHeaders($response->headers->all());
        }

        $data = $payload['errors'] ?? $payload;

        return $this->responses
            ->error($message, is_array($data) ? $data : [], $statusCode)
            ->withHeaders($response->headers->all());
    }

    private function isEnvelope(JsonResponse $response): bool
    {
        $payload = $response->getData(true);

        return is_array($payload)
            && array_key_exists('status', $payload)
            && array_key_exists('message', $payload)
            && array_key_exists('data', $payload);
    }

    /** @param array<string, mixed> $payload */
    private function message(Request $request, array $payload, int $statusCode): string
    {
        if (isset($payload['message']) && is_string($payload['message'])) {
            return $payload['message'];
        }

        return match ($request->route()?->getName()) {
            'api.sponsor_eligibility.show' => 'Sponsor eligibility retrieved successfully.',
            'api.consultation_usages.store' => 'Consultation usage recorded successfully.',
            'api.consultation_reservations.store' => 'Consultation reserved successfully.',
            'api.consultation_reservations.cancel' => 'Consultation reservation cancelled successfully.',
            default => Response::$statusTexts[$statusCode] ?? 'Request completed.',
        };
    }
}
