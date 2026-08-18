<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Http\Requests\InstitutionalCampaigns\IndexInstitutionalCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\WorkspaceResource;
use App\Queries\InstitutionalCampaigns\WorkspaceCampaignQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InstitutionalCampaignIndexController
{
    public function __construct(private WorkspaceCampaignQuery $campaigns) {}

    public function __invoke(IndexInstitutionalCampaignRequest $request): Response
    {
        $workspace = $request->workspace();

        return Inertia::render('institutional-sponsor/campaigns/index', [
            'organization' => new WorkspaceResource($workspace),
            'campaigns' => CampaignResource::collection(
                $this->campaigns->paginate($workspace),
            ),
        ]);
    }
}
