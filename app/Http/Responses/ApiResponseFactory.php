<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final readonly class ApiResponseFactory
{
    /** @param array<array-key, mixed>|object $data */
    public function success(
        array|object $data,
        string $message,
        int $statusCode = JsonResponse::HTTP_OK,
    ): JsonResponse {
        return new JsonResponse([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /** @param array<array-key, mixed>|object $data */
    public function error(
        string $message,
        array|object $data = [],
        int $statusCode = JsonResponse::HTTP_BAD_REQUEST,
    ): JsonResponse {
        return new JsonResponse([
            'status' => false,
            'message' => $message,
            'data' => $data === [] ? (object) [] : $data,
        ], $statusCode);
    }
}
