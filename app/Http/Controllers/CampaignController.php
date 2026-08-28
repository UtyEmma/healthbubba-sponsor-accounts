<?php

namespace App\Http\Controllers;

use App\DTOs\Campaigns\CampaignCreationConfigurationData;
use App\Http\Requests\InstitutionalCampaigns\IndexInstitutionalCampaignRequest;
use App\Http\Requests\InstitutionalCampaigns\ShowInstitutionalCampaignRequest;
use App\Http\Resources\CampaignBeneficiaryCapacityResource;
use App\Http\Resources\CampaignConsultationSummaryResource;
use App\Http\Resources\CampaignCreationConfigurationResource;
use App\Http\Resources\CampaignIndexSummaryResource;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\ConsultationResource;
use App\Http\Resources\WorkspaceBeneficiaryResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Campaign;
use App\Queries\InstitutionalCampaigns\CampaignConsultationSummaryQuery;
use App\Queries\InstitutionalCampaigns\CampaignIndexSummaryQuery;
use App\Queries\InstitutionalCampaigns\WorkspaceCampaignQuery;
use App\Services\WorkspaceBeneficiaries\CampaignBeneficiaryCapacityService;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CampaignController
{
    public function __construct(
        private WorkspaceCampaignQuery $campaigns,
        private CampaignBeneficiaryCapacityService $capacity,
        private CampaignConsultationSummaryQuery $consultationSummary,
        private CampaignIndexSummaryQuery $indexSummary,
    ) {}

    public function index(IndexInstitutionalCampaignRequest $request): Response
    {
        $workspace = $request->workspace();
        $summary = $this->indexSummary->get($workspace);

        return Inertia::render('campaigns/index', [
            'organization' => new WorkspaceResource($workspace),
            'campaigns' => CampaignResource::collection(
                $this->campaigns->paginate($workspace),
            ),
            'summary' => new CampaignIndexSummaryResource(
                $summary,
            ),
            'creation' => new CampaignCreationConfigurationResource(
                new CampaignCreationConfigurationData(
                    currency: $summary->currency,
                    walletBalance: $summary->availableBalance,
                    gpUnitFee: (string) config('campaigns.default_gp_fee'),
                    specialistUnitFee: (string) config('campaigns.default_specialist_fee'),
                    boothSetupUnitFee: (string) config('campaigns.booth_setup_fee'),
                    boothMonthlyUnitFee: (string) config('campaigns.booth_monthly_fee'),
                ),
            ),
        ]);
    }

    public function show(
        ShowInstitutionalCampaignRequest $request,
        Campaign $campaign,
    ): Response {
        $workspace = $request->workspace();
        $campaign = $this->campaigns->prepareForDisplay($campaign);
        $consultationSummary = $this->consultationSummary->get($campaign, $workspace);

        return Inertia::render('campaigns/show', [
            'organization' => new WorkspaceResource($workspace),
            'campaign' => new CampaignResource($campaign),
            'beneficiaries' => WorkspaceBeneficiaryResource::collection(
                $this->campaigns->paginateBeneficiaries($campaign),
            ),
            'capacity' => new CampaignBeneficiaryCapacityResource(
                $this->capacity->summary($campaign),
            ),
            'campaignConsultation' => new CampaignConsultationSummaryResource(
                $consultationSummary,
            ),
            'importResult' => $request->session()->get('import_result'),
            'consultations' => ConsultationResource::collection(
                $this->campaigns->paginateConsultations($campaign),
            ),
        ]);
    }
}
