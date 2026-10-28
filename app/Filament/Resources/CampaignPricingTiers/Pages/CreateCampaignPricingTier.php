<?php

namespace App\Filament\Resources\CampaignPricingTiers\Pages;

use App\Actions\Campaigns\SaveCampaignPricingTierAction;
use App\DTOs\Campaigns\SaveCampaignPricingTierData;
use App\Filament\Resources\CampaignPricingTiers\CampaignPricingTierResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class CreateCampaignPricingTier extends CreateRecord
{
    protected static string $resource = CampaignPricingTierResource::class;

    private SaveCampaignPricingTierAction $savePricingTier;

    public function boot(SaveCampaignPricingTierAction $savePricingTier): void
    {
        $this->savePricingTier = $savePricingTier;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $administrator = Auth::user();
        abort_unless($administrator instanceof User, 403);

        return $this->savePricingTier->execute(
            SaveCampaignPricingTierData::fromArray($data),
            $administrator,
        );
    }
}
