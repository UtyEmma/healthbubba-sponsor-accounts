<?php

namespace App\Filament\Resources\Subscriptions\Actions;

use App\Actions\Admin\AssignWorkspaceSubscriptionAction;
use App\DTOs\Admin\AssignWorkspaceSubscriptionData;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Exceptions\Payments\CheckoutUnavailable;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class SubscriptionAdminActions
{
    public static function create(): Action
    {
        return Action::make('createSubscription')
            ->label('Create subscription')
            ->icon(Heroicon::OutlinedReceiptPercent)
            ->authorize('create', Subscription::class)
            ->rateLimit(10)
            ->modalHeading('Create workspace subscription')
            ->modalDescription('Choose a workspace and compatible plan. The subscription starts immediately; an active subscription is replaced only after eligibility checks pass.')
            ->modalSubmitActionLabel('Create subscription')
            ->schema([
                Select::make('workspace_id')
                    ->label('Workspace')
                    ->searchable()
                    ->getSearchResultsUsing(static fn (string $search): array => Workspace::query()
                        ->where('name', 'like', "%{$search}%")
                        ->orderBy('name')
                        ->limit(50)
                        ->get(['id', 'name', 'type'])
                        ->mapWithKeys(static fn (Workspace $workspace): array => [
                            $workspace->getKey() => "{$workspace->name} · {$workspace->type->label()}",
                        ])
                        ->all())
                    ->getOptionLabelUsing(static function (mixed $value): ?string {
                        $workspace = Workspace::query()->find($value);

                        return $workspace instanceof Workspace
                            ? "{$workspace->name} · {$workspace->type->label()}"
                            : null;
                    })
                    ->live()
                    ->afterStateUpdated(static function (Set $set): void {
                        $set('plan_id', null);
                    })
                    ->required(),
                Select::make('plan_id')
                    ->label('Plan')
                    ->options(static function (Get $get): array {
                        $accountType = Workspace::query()
                            ->whereKey($get('workspace_id'))
                            ->value('type');

                        if (blank($accountType)) {
                            return [];
                        }

                        return Plan::query()
                            ->where('account_type', $accountType)
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                            ->all();
                    })
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
                Select::make('billing_cycle')
                    ->label('Individual billing interval')
                    ->options(collect(IndividualPlanBillingCycle::cases())
                        ->mapWithKeys(fn (IndividualPlanBillingCycle $cycle): array => [
                            $cycle->value => $cycle->label(),
                        ])->all())
                    ->default(IndividualPlanBillingCycle::MONTHLY->value)
                    ->helperText('Used only when the selected workspace is an Individual account.'),
                Toggle::make('complimentary_renews')
                    ->label('Ongoing complimentary renewal')
                    ->helperText('Automatically renew without charging the workspace wallet.')
                    ->visible(static fn (Get $get): bool => $get('funding_source') === SubscriptionFundingSource::Complimentary->value),
            ])
            ->requiresConfirmation()
            ->action(static function (array $data, AssignWorkspaceSubscriptionAction $assignSubscription): void {
                $administrator = Auth::user();
                abort_unless($administrator instanceof User, 403);

                try {
                    $assignSubscription->execute($administrator, new AssignWorkspaceSubscriptionData(
                        workspaceId: (int) $data['workspace_id'],
                        planId: (int) $data['plan_id'],
                        fundingSource: SubscriptionFundingSource::from((string) $data['funding_source']),
                        additionalCapacity: (int) ($data['additional_capacity'] ?? 0),
                        complimentaryRenews: (bool) ($data['complimentary_renews'] ?? false),
                        billingCycle: isset($data['billing_cycle'])
                            ? IndividualPlanBillingCycle::from((string) $data['billing_cycle'])
                            : null,
                    ));
                } catch (CheckoutUnavailable $exception) {
                    throw ValidationException::withMessages([
                        'plan_id' => $exception->getMessage(),
                    ]);
                }

                Notification::make()
                    ->title('Workspace subscription created')
                    ->success()
                    ->send();
            });
    }
}
