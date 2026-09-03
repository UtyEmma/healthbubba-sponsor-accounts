<?php

namespace App\Http\Controllers\Institutional;

use App\Http\Requests\Institutional\ViewInstitutionalConsultationsRequest;
use App\Http\Resources\InstitutionalCampaignOptionResource;
use App\Http\Resources\InstitutionalConsultationResource;
use App\Models\Campaign;
use App\Queries\Institutional\InstitutionalConsultationQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InstitutionalConsultationController
{
    public function __construct(private InstitutionalConsultationQuery $consultations) {}

    public function __invoke(ViewInstitutionalConsultationsRequest $request): Response
    {
        return Inertia::render('institutional-sponsor/consultations/index', [
            'consultations' => InstitutionalConsultationResource::collection(
                $this->consultations->paginate(
                    $request->workspace(),
                    $request->string('campaign')->trim()->value() ?: null,
                ),
            ),
            'campaigns' => InstitutionalCampaignOptionResource::collection(
                Campaign::query()->whereBelongsTo($request->workspace())->orderBy('name')->get([
                    'id', 'name', 'slug', 'location', 'end_date', 'start_date', 'status',
                    'ended_at', 'estimated_beneficiaries', 'beneficiary_limit',
                ]),
            ),
            'filters' => $request->safe()->only(['campaign']),
        ]);
    }
}
