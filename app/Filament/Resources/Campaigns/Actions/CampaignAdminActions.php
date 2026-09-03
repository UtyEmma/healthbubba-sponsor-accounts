<?php

namespace App\Filament\Resources\Campaigns\Actions;

use App\Actions\Campaigns\DeactivateCampaignBoothAction;
use App\Actions\Campaigns\EndCampaignAction;
use App\Actions\Campaigns\PauseCampaignAction;
use App\Actions\Campaigns\ResumeCampaignAction;
use App\Actions\Campaigns\RunCampaignMonthlyDeductionsAction;
use App\Enums\CampaignBoothStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

final class CampaignAdminActions
{
    public static function pause(): Action
    {
        return Action::make('pauseCampaign')
            ->label('Pause campaign')
            ->icon(Heroicon::OutlinedPause)
            ->requiresConfirmation()
            ->modalDescription('New healthcare usage will be blocked until this campaign is resumed.')
            ->visible(static fn (Campaign $record): bool => $record->lifecycleStatus() === CampaignStatus::IN_PROGRESS)
            ->action(static function (Campaign $record, PauseCampaignAction $pause): void {
                $pause->execute($record);
                $record->refresh();

                Notification::make()
                    ->title('Campaign paused')
                    ->success()
                    ->send();
            });
    }

    public static function resume(): Action
    {
        return Action::make('resumeCampaign')
            ->label('Resume campaign')
            ->icon(Heroicon::OutlinedPlay)
            ->requiresConfirmation()
            ->visible(static fn (Campaign $record): bool => $record->lifecycleStatus() === CampaignStatus::PAUSED)
            ->action(static function (Campaign $record, ResumeCampaignAction $resume): void {
                $resume->execute($record);
                $record->refresh();

                Notification::make()
                    ->title('Campaign resumed')
                    ->success()
                    ->send();
            });
    }

    public static function deactivateBooths(): Action
    {
        return Action::make('deactivateCampaignBooths')
            ->label('Deactivate booths')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Deactivate all active booths?')
            ->modalDescription('Future booth service charges will stop. Already-paid service periods are not refunded.')
            ->visible(static fn (Campaign $record): bool => (int) $record->active_booths_count > 0
                || ($record->booth_activated_at !== null && $record->booth_deactivated_at === null))
            ->action(static function (Campaign $record, DeactivateCampaignBoothAction $deactivate): void {
                $deactivate->execute($record);
                $record->refresh()->loadCount([
                    'booths as active_booths_count' => static fn (Builder $query): Builder => $query
                        ->whereIn('status', [
                            CampaignBoothStatus::Active,
                            CampaignBoothStatus::GracePeriod,
                            CampaignBoothStatus::Suspended,
                        ]),
                ]);

                Notification::make()
                    ->title('Campaign booths deactivated')
                    ->success()
                    ->send();
            });
    }

    public static function runMonthlyDeductions(): Action
    {
        return Action::make('runMonthlyDeductions')
            ->label('Run monthly deductions')
            ->icon(Heroicon::OutlinedCreditCard)
            ->requiresConfirmation()
            ->modalDescription('All due booth service and operating costs for this campaign will be charged from its workspace wallet.')
            ->visible(static fn (Campaign $record): bool => (int) $record->active_recurring_costs_count > 0
                && $record->lifecycleStatus() !== CampaignStatus::COMPLETED)
            ->action(static function (Campaign $record, RunCampaignMonthlyDeductionsAction $runDeductions): void {
                $result = $runDeductions->execute($record);

                if ($result->chargesCompleted === 0) {
                    Notification::make()
                        ->title('No deductions were completed')
                        ->body('No charges are currently due, or the workspace wallet has insufficient funds.')
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title("{$result->chargesCompleted} monthly deduction(s) completed")
                    ->body("Checked {$result->costsChecked} active recurring cost(s).")
                    ->success()
                    ->send();
            });
    }

    public static function end(): Action
    {
        return Action::make('endCampaign')
            ->label('End campaign')
            ->icon(Heroicon::OutlinedStopCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('End this campaign?')
            ->modalDescription('Unused, unencumbered healthcare allocation will be returned and future recurring charges will stop.')
            ->visible(static fn (Campaign $record): bool => $record->lifecycleStatus() !== CampaignStatus::COMPLETED)
            ->action(static function (Campaign $record, EndCampaignAction $end): void {
                $end->execute($record);
                $record->refresh();

                Notification::make()
                    ->title('Campaign ended')
                    ->success()
                    ->send();
            });
    }
}
