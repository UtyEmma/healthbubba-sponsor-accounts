<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Requests\Consultations\IndexConsultationRequest;
use App\Http\Resources\ConsultationResource;
use App\Queries\Consultations\WorkspaceConsultationQuery;
use App\Services\Consultations\ConsultationCoverageService;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ConsultationController
{
    public function __construct(
        private WorkspaceConsultationQuery $consultations,
        private ConsultationCoverageService $coverage,
    ) {}

    public function index(IndexConsultationRequest $request): Response
    {
        $workspace = $request->workspace();

        return Inertia::render('consultations/index', [
            'consultations' => ConsultationResource::collection(
                $this->consultations->paginate($workspace),
            ),
            'coverage' => $this->coverage->summary($workspace),
        ]);
    }
}
