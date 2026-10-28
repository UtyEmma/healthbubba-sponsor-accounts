<?php

namespace App\Filament\Resources\CampaignPricingTiers\Pages;

use App\Actions\Campaigns\SaveCampaignPricingTierAction;
use App\DTOs\Campaigns\SaveCampaignPricingTierData;
use App\Filament\Resources\CampaignPricingTiers\CampaignPricingTierResource;
use App\Models\CampaignPricingTier;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class EditCampaignPricingTier extends EditRecord
{
    protected static string $resource = CampaignPricingTierResource::class;

    private SaveCampaignPricingTierAction $savePricingTier;

    public function boot(SaveCampaignPricingTierAction $savePricingTier): void
    {
        $this->savePricingTier = $savePricingTier;
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $administrator = Auth::user();
        abort_unless($administrator instanceof User && $record instanceof CampaignPricingTier, 403);

        return $this->savePricingTier->execute(
            SaveCampaignPricingTierData::fromArray($data),
            $administrator,
            $record,
        );
    }
}
