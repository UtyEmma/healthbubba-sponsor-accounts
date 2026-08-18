<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstitutionalCampaigns\IndexInstitutionalCampaignRequest;
use App\Http\Requests\InstitutionalCampaigns\ShowInstitutionalCampaignRequest;
use App\Http\Resources\CampaignBeneficiaryCapacityResource;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\WorkspaceBeneficiaryResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Campaign;
use App\Queries\InstitutionalCampaigns\WorkspaceCampaignQuery;
use App\Services\WorkspaceBeneficiaries\CampaignBeneficiaryCapacityService;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CampaignController
{
    public function __construct(
        private WorkspaceCampaignQuery $campaigns,
        private CampaignBeneficiaryCapacityService $capacity,
    ) {}

    public function index(IndexInstitutionalCampaignRequest $request): Response
    {
        $workspace = $request->workspace();

        return Inertia::render('campaigns/index', [
            'organization' => new WorkspaceResource($workspace),
            'campaigns' => CampaignResource::collection(
                $this->campaigns->paginate($workspace),
            ),
        ]);
    }

    public function show(
        ShowInstitutionalCampaignRequest $request,
        Campaign $campaign,
    ): Response {
        $workspace = $request->workspace();
        $summary = $this->capacity->summary($campaign, $workspace);
        $campaign = $this->campaigns->prepareForDisplay($campaign);

        return Inertia::render('campaigns/show', [
            'organization' => new WorkspaceResource($workspace),
            'campaign' => new CampaignResource($campaign),
            'capacity' => new CampaignBeneficiaryCapacityResource($summary),
            'beneficiaries' => WorkspaceBeneficiaryResource::collection(
                $this->campaigns->paginateBeneficiaries($campaign),
            ),
        ]);
    }
}
