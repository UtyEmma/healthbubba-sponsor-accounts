<?php

namespace App\Http\Controllers\InstitutionalCampaigns;

use App\Actions\Campaigns\PurchaseConsultationQuotaAction;
use App\DTOs\Campaigns\PurchaseConsultationQuotaData;
use App\Enums\Consultations\ConsultationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionalCampaigns\PurchaseConsultationQuotaRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;

final class PurchaseCampaignConsultationQuotaController extends Controller
{
    public function __construct(
        private PurchaseConsultationQuotaAction $purchase,
    ) {}

    public function __invoke(
        PurchaseConsultationQuotaRequest $request,
        Campaign $campaign,
    ): RedirectResponse {
        $this->purchase->execute(
            new PurchaseConsultationQuotaData(
                workspace: $request->workspace(),
                campaign: $campaign,
                user: $request->onboardingUser(),
                consultationType: ConsultationType::from($request->validated('consultation_type')),
                quantity: (int) $request->validated('quantity'),
            ),
        );

        return to_route('campaigns.show', $campaign)
            ->with('success', 'Consultation quota purchased successfully.');
    }
}
