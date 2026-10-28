<?php

namespace App\Filament\Resources\Campaigns\Actions;

use App\Actions\Campaigns\AllocateMoreToCampaignAction;
use App\Actions\Campaigns\DeactivateCampaignBoothAction;
use App\Actions\Campaigns\EndCampaignAction;
use App\Actions\Campaigns\PauseCampaignAction;
use App\Actions\Campaigns\ResumeCampaignAction;
use App\Actions\Campaigns\RunCampaignMonthlyDeductionsAction;
use App\DTOs\Campaigns\AllocateMoreToCampaignData;
use App\Enums\CampaignBoothStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class CampaignAdminActions
{
    public static function allocateMore(): Action
    {
        return Action::make('allocateMore')
            ->label('Allocate more')
            ->icon(Heroicon::OutlinedPlus)
            ->visible(static fn (Campaign $record): bool => $record->lifecycleStatus() !== CampaignStatus::COMPLETED)
            ->schema([
                TextInput::make('scheduled_units')
                    ->label('Scheduled consultation units')
                    ->numeric()->integer()->minValue(0)->default(0)->required(),
                TextInput::make('instant_units')
                    ->label('Instant consultation units')
                    ->numeric()->integer()->minValue(0)->default(0)->required(),
                TextInput::make('medication_budget')->numeric()->minValue(0)->default(0)->prefix('₦')->required(),
                TextInput::make('laboratory_budget')->numeric()->minValue(0)->default(0)->prefix('₦')->required(),
            ])
            ->requiresConfirmation()
            ->modalDescription('This reserves sponsor-funded value from the workspace wallet at the campaign’s server-controlled prices.')
            ->action(static function (array $data, Campaign $record, AllocateMoreToCampaignAction $allocate): void {
                $administrator = Auth::user();
                abort_unless($administrator instanceof User && $administrator->isAdmin(), 403);

                $allocate->execute(new AllocateMoreToCampaignData(
                    workspace: $record->workspace,
                    user: $administrator,
                    campaign: $record,
                    scheduledUnits: (int) $data['scheduled_units'],
                    instantUnits: (int) $data['instant_units'],
                    medicationBudget: (string) $data['medication_budget'],
                    laboratoryBudget: (string) $data['laboratory_budget'],
                ));

                Notification::make()->title('Campaign allocation increased')->success()->send();
            });
    }

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
            ->modalDescription('All due campaign service, booth service, and operating costs will be charged from the workspace wallet.')
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
