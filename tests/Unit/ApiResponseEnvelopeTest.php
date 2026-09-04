<?php

use App\DTOs\Consultations\PatientConsultationSponsorshipData;
use App\Http\Middleware\EnsureApiResponseEnvelope;
use App\Http\Resources\PatientConsultationSponsorshipResource;
use App\Http\Responses\ApiResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

it('wraps successful json responses in the api contract', function () {
    $middleware = new EnsureApiResponseEnvelope(new ApiResponseFactory);
    $request = Request::create('/api/example', 'GET');

    $response = $middleware->handle(
        $request,
        fn (): JsonResponse => new JsonResponse(['value' => 42]),
    );

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getData(true))->toBe([
            'status' => true,
            'message' => 'OK',
            'data' => ['value' => 42],
        ]);
});

it('preserves an empty result list inside data', function () {
    $middleware = new EnsureApiResponseEnvelope(new ApiResponseFactory);
    $request = Request::create('/api/sponsor-eligibility', 'POST');

    $response = $middleware->handle(
        $request,
        fn (): JsonResponse => new JsonResponse([]),
    );

    expect($response->getData(true))->toBe([
        'status' => true,
        'message' => 'OK',
        'data' => [],
    ]);
});

it('returns an empty sponsor list when the patient has no active sponsorships', function () {
    $resource = new PatientConsultationSponsorshipResource(
        new PatientConsultationSponsorshipData(patientId: 15, sponsors: []),
    );

    expect($resource->resolve(Request::create('/api/sponsor-eligibility', 'POST')))
        ->toBe([]);
});

it('places validation errors directly inside the data object', function () {
    $middleware = new EnsureApiResponseEnvelope(new ApiResponseFactory);
    $request = Request::create('/api/example', 'POST');

    $response = $middleware->handle(
        $request,
        fn (): JsonResponse => new JsonResponse([
            'message' => 'The given data was invalid.',
            'errors' => ['patient_id' => ['The patient id field is required.']],
        ], 422),
    );

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getData(true))->toBe([
            'status' => false,
            'message' => 'The given data was invalid.',
            'data' => ['patient_id' => ['The patient id field is required.']],
        ]);
});
