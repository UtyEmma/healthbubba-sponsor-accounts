<?php

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Actions\Campaigns\ActivateCampaignBoothRecordAction;
use App\Actions\Campaigns\DeactivateCampaignBoothRecordAction;
use App\Actions\Campaigns\RunCampaignBoothMonthlyDeductionAction;
use App\Enums\CampaignBoothStatus;
use App\Models\CampaignBooth;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class BoothsRelationManager extends RelationManager
{
    protected static string $relationship = 'booths';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('community'),
                TextColumn::make('preferred_deployment_date')->date(),
                TextColumn::make('status')->badge(),
                TextColumn::make('monthly_fee')->money('NGN'),
                TextColumn::make('activated_at')->dateTime()->placeholder('Awaiting deployment'),
                TextColumn::make('paid_through')->date()->placeholder('Not billed'),
            ])
            ->recordActions([
                Action::make('activate')
                    ->requiresConfirmation()
                    ->visible(static fn (CampaignBooth $record): bool => $record->status === CampaignBoothStatus::Requested)
                    ->action(static function (
                        CampaignBooth $record,
                        ActivateCampaignBoothRecordAction $activate,
                    ): void {
                        $activate->execute($record);

                        Notification::make()
                            ->title('Booth activated')
                            ->success()
                            ->send();
                    }),
                Action::make('deactivate')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(static fn (CampaignBooth $record): bool => in_array($record->status, [
                        CampaignBoothStatus::Active,
                        CampaignBoothStatus::GracePeriod,
                        CampaignBoothStatus::Suspended,
                    ], true))
                    ->action(static function (
                        CampaignBooth $record,
                        DeactivateCampaignBoothRecordAction $deactivate,
                    ): void {
                        $deactivate->execute($record);

                        Notification::make()
                            ->title('Booth deactivated')
                            ->success()
                            ->send();
                    }),
                Action::make('runMonthlyDeduction')
                    ->label('Run monthly deduction')
                    ->requiresConfirmation()
                    ->visible(static fn (CampaignBooth $record): bool => in_array($record->status, [
                        CampaignBoothStatus::Active,
                        CampaignBoothStatus::GracePeriod,
                        CampaignBoothStatus::Suspended,
                    ], true))
                    ->action(static function (
                        CampaignBooth $record,
                        RunCampaignBoothMonthlyDeductionAction $runDeduction,
                    ): void {
                        $charged = $runDeduction->execute($record);
                        $notification = Notification::make()
                            ->title($charged ? 'Monthly deduction completed' : 'No deduction was completed');

                        if ($charged) {
                            $notification->success();
                        } else {
                            $notification
                                ->body('No service charge is due, or the workspace wallet has insufficient funds.')
                                ->warning();
                        }

                        $notification->send();
                    }),
            ]);
    }
}
