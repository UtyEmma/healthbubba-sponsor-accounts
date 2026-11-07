<?php

namespace App\Filament\Resources\Workspaces\Actions;

use App\Actions\Admin\AssignWorkspaceSubscriptionAction;
use App\Actions\Admin\RecordAdminWorkspaceFundingAction;
use App\DTOs\Admin\AssignWorkspaceSubscriptionData;
use App\DTOs\Admin\RecordAdminWorkspaceFundingData;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\ValueObjects\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class WorkspaceAdminActions
{
    public static function recordFunding(): Action
    {
        return Action::make('recordFunding')
            ->label('Fund wallet')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->authorize('update')
            ->rateLimit(10)
            ->modalHeading('Fund workspace wallet')
            ->modalDescription('Credit sponsor-paid funds to this workspace. The administrator and supplied payment reference are retained for audit; this is not a grant.')
            ->modalSubmitActionLabel('Credit wallet')
            ->schema([
                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix(static fn (Workspace $record): string => $record->wallet->currency)
                    ->required(),
                TextInput::make('external_reference')
                    ->label('Payment/reference number')
                    ->maxLength(255),
                Textarea::make('note')
                    ->maxLength(1000),
            ])
            ->requiresConfirmation()
            ->action(static function (
                array $data,
                Workspace $record,
                RecordAdminWorkspaceFundingAction $recordFunding,
            ): void {
                $administrator = Auth::user();
                abort_unless($administrator instanceof User, 403);

                $currency = $record->wallet->currency;
                $recordFunding->execute($administrator, new RecordAdminWorkspaceFundingData(
                    workspaceId: (int) $record->getKey(),
                    amount: Money::fromMajor((string) $data['amount'], $currency),
                    note: filled($data['note'] ?? null) ? (string) $data['note'] : null,
                    externalReference: filled($data['external_reference'] ?? null)
                        ? (string) $data['external_reference']
                        : null,
                ));

                $record->refresh()->load('wallet');

                Notification::make()
                    ->title('Workspace wallet funded')
                    ->success()
                    ->send();
            });
    }

    public static function assignSubscription(): Action
    {
        return Action::make('assignSubscription')
            ->label('Create subscription')
            ->icon(Heroicon::OutlinedReceiptPercent)
            ->authorize('update')
            ->rateLimit(10)
            ->modalHeading('Create workspace subscription')
            ->modalDescription('The subscription starts immediately. An active subscription is replaced immediately, subject to capacity and usage eligibility checks.')
            ->modalSubmitActionLabel('Create subscription')
            ->schema(self::subscriptionSchema())
            ->requiresConfirmation()
            ->action(static function (
                array $data,
                Workspace $record,
                AssignWorkspaceSubscriptionAction $assignSubscription,
            ): void {
                $administrator = Auth::user();
                abort_unless($administrator instanceof User, 403);

                try {
                    $assignSubscription->execute($administrator, self::subscriptionData(
                        data: $data,
                        workspaceId: (int) $record->getKey(),
                    ));
                } catch (CheckoutUnavailable $exception) {
                    throw ValidationException::withMessages([
                        'plan_id' => $exception->getMessage(),
                    ]);
                }

                $record->refresh()->load('latestSubscription.plan');

                Notification::make()
                    ->title('Workspace subscription created')
                    ->success()
                    ->send();
            });
    }

    /** @return list<Select|TextInput|Toggle> */
    public static function subscriptionSchema(): array
    {
        return [
            Select::make('plan_id')
                ->label('Plan')
                ->options(static fn (Workspace $record): array => Plan::query()
                    ->where('account_type', $record->type)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->required(),
            Select::make('funding_source')
                ->label('Funding source')
                ->options([
                    SubscriptionFundingSource::Wallet->value => 'Workspace wallet',
                    SubscriptionFundingSource::Complimentary->value => 'Complimentary',
                ])
                ->default(SubscriptionFundingSource::Wallet->value)
                ->helperText('Wallet assignments use the server-calculated plan price and fail without sufficient balance.')
                ->required()
                ->live(),
            TextInput::make('additional_capacity')
                ->label('Additional capacity')
                ->helperText('Optional capacity above the plan’s included allowance, where supported.')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->default(0),
            Toggle::make('complimentary_renews')
                ->label('Ongoing complimentary renewal')
                ->helperText('Automatically renew without charging the workspace wallet.')
                ->visible(static fn (Get $get): bool => $get('funding_source') === SubscriptionFundingSource::Complimentary->value),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function subscriptionData(array $data, int $workspaceId): AssignWorkspaceSubscriptionData
    {
        return new AssignWorkspaceSubscriptionData(
            workspaceId: $workspaceId,
            planId: (int) $data['plan_id'],
            fundingSource: SubscriptionFundingSource::from((string) $data['funding_source']),
            additionalCapacity: (int) ($data['additional_capacity'] ?? 0),
            complimentaryRenews: (bool) ($data['complimentary_renews'] ?? false),
        );
    }
}
