<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Http\Requests\InstitutionalCampaigns\ShowInstitutionalCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Campaign;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InstitutionalCampaignShowController
{
    public function __invoke(
        ShowInstitutionalCampaignRequest $request,
        Campaign $campaign,
    ): Response {
        $campaign->loadCount(['beneficiaries', 'activeBeneficiaries']);

        return Inertia::render('institutional-sponsor/coverage/index', [
            'organization' => new WorkspaceResource($request->workspace()),
            'campaign' => new CampaignResource($campaign),
        ]);
    }
}
