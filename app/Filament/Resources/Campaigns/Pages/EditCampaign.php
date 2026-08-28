<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Actions\Campaigns\ActivateCampaignBoothAction;
use App\Actions\Campaigns\DeactivateCampaignBoothAction;
use App\Actions\Campaigns\EndCampaignAction;
use App\Actions\Campaigns\PauseCampaignAction;
use App\Actions\Campaigns\ResumeCampaignAction;
use App\Enums\CampaignStatus;
use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pause')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->lifecycleStatus() === CampaignStatus::IN_PROGRESS)
                ->action(fn () => app(PauseCampaignAction::class)->execute($this->record)),
            Action::make('resume')
                ->visible(fn (): bool => $this->record->status === CampaignStatus::PAUSED)
                ->action(fn () => app(ResumeCampaignAction::class)->execute($this->record)),
            Action::make('activateBooth')
                ->label('Activate booth')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->booth_required && $this->record->booth_activated_at === null)
                ->action(fn () => app(ActivateCampaignBoothAction::class)->execute($this->record)),
            Action::make('deactivateBooth')
                ->label('Deactivate booth')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->booth_activated_at !== null && $this->record->booth_deactivated_at === null)
                ->action(fn () => app(DeactivateCampaignBoothAction::class)->execute($this->record)),
            Action::make('end')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->lifecycleStatus() !== CampaignStatus::COMPLETED)
                ->action(fn () => app(EndCampaignAction::class)->execute($this->record)),
            ViewAction::make(),
        ];
    }
}
