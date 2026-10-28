<?php

namespace App\Filament\Resources\Workspaces\Pages;

use App\Actions\Admin\AssignWorkspaceSubscriptionAction;
use App\Actions\Admin\RecordAdminWorkspaceFundingAction;
use App\DTOs\Admin\AssignWorkspaceSubscriptionData;
use App\DTOs\Admin\RecordAdminWorkspaceFundingData;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Filament\Resources\Workspaces\WorkspaceResource;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\ValueObjects\Money;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;

class ViewWorkspace extends ViewRecord
{
    protected static string $resource = WorkspaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordFunding')
                ->label('Record sponsor funding')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->modalDescription('Credit sponsor-paid funds to this workspace. The administrator is recorded for audit; this is not a grant.')
                ->schema([
                    TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->minValue(0.01)
                        ->prefix(fn (Workspace $record): string => $record->wallet->currency)
                        ->required(),
                    TextInput::make('external_reference')
                        ->label('Payment/reference number')
                        ->maxLength(255),
                    Textarea::make('note')
                        ->maxLength(1000),
                ])
                ->requiresConfirmation()
                ->action(function (array $data, Workspace $record, RecordAdminWorkspaceFundingAction $action): void {
                    $administrator = Auth::user();
                    abort_unless($administrator instanceof User, 403);

                    $action->execute($administrator, new RecordAdminWorkspaceFundingData(
                        workspaceId: (int) $record->getKey(),
                        amount: Money::fromMajor((string) $data['amount'], $record->wallet->currency),
                        note: filled($data['note'] ?? null) ? (string) $data['note'] : null,
                        externalReference: filled($data['external_reference'] ?? null) ? (string) $data['external_reference'] : null,
                    ));

                    Notification::make()->title('Sponsor funding recorded')->success()->send();
                }),
            Action::make('assignPlan')
                ->label('Assign plan')
                ->icon('heroicon-o-receipt-percent')
                ->schema([
                    Select::make('plan_id')
                        ->label('Plan')
                        ->options(fn (Workspace $record): array => Plan::query()
                            ->where('account_type', $record->type)
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    Select::make('funding_source')
                        ->options([
                            SubscriptionFundingSource::Wallet->value => 'Workspace wallet',
                            SubscriptionFundingSource::Complimentary->value => 'Complimentary',
                        ])
                        ->default(SubscriptionFundingSource::Wallet->value)
                        ->required()
                        ->live(),
                    TextInput::make('additional_capacity')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0),
                    Toggle::make('complimentary_renews')
                        ->label('Ongoing complimentary renewal')
                        ->visible(fn (Get $get): bool => $get('funding_source') === SubscriptionFundingSource::Complimentary->value),
                ])
                ->requiresConfirmation()
                ->modalDescription('Plan replacements apply immediately. Reductions are blocked when current capacity or usage exceeds the target plan.')
                ->action(function (array $data, Workspace $record, AssignWorkspaceSubscriptionAction $action): void {
                    $administrator = Auth::user();
                    abort_unless($administrator instanceof User, 403);

                    $action->execute($administrator, new AssignWorkspaceSubscriptionData(
                        workspaceId: (int) $record->getKey(),
                        planId: (int) $data['plan_id'],
                        fundingSource: SubscriptionFundingSource::from((string) $data['funding_source']),
                        additionalCapacity: (int) ($data['additional_capacity'] ?? 0),
                        complimentaryRenews: (bool) ($data['complimentary_renews'] ?? false),
                    ));

                    Notification::make()->title('Workspace plan assigned')->success()->send();
                }),
            EditAction::make(),
        ];
    }
}
