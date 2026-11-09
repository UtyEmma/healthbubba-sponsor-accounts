<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Admin\ProvisionSponsorAccountAction;
use App\DTOs\Admin\AssignWorkspaceSubscriptionData;
use App\DTOs\Admin\ProvisionSponsorAccountData;
use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use App\Enums\InstitutionalOrganizationType;
use App\Enums\NigeriaState;
use App\Enums\Subscriptions\SubscriptionFundingSource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Workspaces\WorkspaceResource;
use App\Models\Plan;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    use HasWizard;

    protected static string $resource = UserResource::class;

    private ?int $createdWorkspaceId = null;

    /** @return list<Step> */
    protected function getSteps(): array
    {
        return [
            Step::make('Identity')
                ->description('Create a new user or reuse an existing HealthBubba identity.')
                ->columns(2)
                ->schema([
                    Select::make('identity_mode')
                        ->label('User')
                        ->options(['new' => 'Create new user', 'existing' => 'Use existing user'])
                        ->default('new')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('existing_user_id', null);
                            $set('name', null);
                            $set('email', null);
                            $set('phone', null);
                        }),
                    Select::make('existing_user_id')
                        ->label('Existing user')
                        ->options(fn (): array => User::query()
                            ->orderBy('name')
                            ->limit(100)
                            ->get(['id', 'name', 'email'])
                            ->mapWithKeys(fn (User $user): array => [
                                $user->getKey() => "{$user->name} · {$user->email}",
                            ])->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->required(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            $user = User::query()->find($state);

                            if (! $user instanceof User) {
                                return;
                            }

                            $set('name', $user->name);
                            $set('email', $user->email);
                            $set('phone', $user->phone);
                            $set('status', $user->status->value);
                            $set('role', $user->role->value);
                            $set('email_verified', $user->email_verified_at !== null);
                            $set('phone_verified', $user->phone_verified_at !== null);
                            $set('account_verified', $user->account_verified_at !== null);
                        }),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->disabled(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->dehydrated(),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(User::class, 'email', modifyRuleUsing: fn ($rule, Get $get) => $get('identity_mode') === 'existing' ? $rule->ignore($get('existing_user_id')) : $rule)
                        ->disabled(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->dehydrated(),
                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(30)
                        ->disabled(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->dehydrated(),
                    Select::make('status')
                        ->options(collect(Status::cases())->mapWithKeys(fn (Status $status): array => [$status->value => $status->label()])->all())
                        ->default(Status::ACTIVE->value)
                        ->required()
                        ->disabled(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->dehydrated(),
                    Select::make('role')
                        ->options(function (): array {
                            $actor = Auth::user();
                            $roles = $actor instanceof User && $actor->isSuperAdmin()
                                ? Roles::cases()
                                : [Roles::USER];

                            return collect($roles)->mapWithKeys(fn (Roles $role): array => [
                                $role->value => str($role->value)->headline()->toString(),
                            ])->all();
                        })
                        ->default(Roles::USER->value)
                        ->required()
                        ->disabled(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->dehydrated(),
                ]),
            Step::make('Access')
                ->description('Set credentials and verification state for a new identity.')
                ->columns(2)
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->minLength(6)
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('identity_mode') === 'new')
                        ->helperText('Leave blank to generate a secure credential and send a password setup invitation.'),
                    TextInput::make('password_confirmation')
                        ->password()
                        ->revealable()
                        ->same('password')
                        ->visible(fn (Get $get): bool => $get('identity_mode') === 'new' && filled($get('password'))),
                    Toggle::make('send_invitation')
                        ->label('Send password setup invitation')
                        ->default(true)
                        ->visible(fn (Get $get): bool => $get('identity_mode') === 'new')
                        ->helperText('Required automatically when no password is supplied.'),
                    Toggle::make('email_verified')
                        ->label('Email verified')
                        ->default(false)
                        ->disabled(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->dehydrated(),
                    Toggle::make('phone_verified')
                        ->label('Phone verified')
                        ->default(false)
                        ->disabled(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->dehydrated(),
                    Toggle::make('account_verified')
                        ->label('Account verified')
                        ->default(false)
                        ->disabled(fn (Get $get): bool => $get('identity_mode') === 'existing')
                        ->dehydrated(),
                ]),
            Step::make('Workspace')
                ->description('Create the owned sponsor workspace.')
                ->columns(2)
                ->schema([
                    Select::make('account_type')
                        ->options(AccountTypes::options())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set): mixed => $set('plan_id', null)),
                    TextInput::make('workspace_name')
                        ->label('Workspace name')
                        ->required()
                        ->maxLength(255),
                    Select::make('organization_type')
                        ->options(collect(InstitutionalOrganizationType::cases())->mapWithKeys(fn (InstitutionalOrganizationType $type): array => [$type->value => $type->label()])->all())
                        ->required(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value)
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value),
                    TextInput::make('country_code')
                        ->default('NG')
                        ->maxLength(2)
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value),
                    Select::make('state_code')
                        ->options(collect(NigeriaState::cases())->mapWithKeys(fn (NigeriaState $state): array => [$state->value => $state->label()])->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value),
                    TextInput::make('official_email')
                        ->email()
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value),
                    TextInput::make('official_phone')
                        ->tel()
                        ->maxLength(30)
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value),
                    TextInput::make('member_job_title')
                        ->label('Owner job title')
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value),
                    Toggle::make('authorization_confirmed')
                        ->label('Organization authorization confirmed')
                        ->default(false)
                        ->required(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value)
                        ->visible(fn (Get $get): bool => $get('account_type') === AccountTypes::INSTITUTION->value),
                ]),
            Step::make('Subscription')
                ->description('Optionally assign the initial account-compatible plan.')
                ->columns(2)
                ->schema([
                    Toggle::make('assign_plan')
                        ->label('Assign a plan now')
                        ->default(true)
                        ->live(),
                    Select::make('plan_id')
                        ->options(fn (Get $get): array => blank($get('account_type')) ? [] : Plan::query()
                            ->where('account_type', $get('account_type'))
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                            ->all())
                        ->required(fn (Get $get): bool => (bool) $get('assign_plan'))
                        ->searchable()
                        ->visible(fn (Get $get): bool => (bool) $get('assign_plan')),
                    Select::make('subscription_funding_source')
                        ->label('Plan funding')
                        ->options([
                            SubscriptionFundingSource::Wallet->value => 'Workspace wallet',
                            SubscriptionFundingSource::Complimentary->value => 'Complimentary',
                        ])
                        ->default(SubscriptionFundingSource::Wallet->value)
                        ->required(fn (Get $get): bool => (bool) $get('assign_plan'))
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('assign_plan')),
                    TextInput::make('additional_capacity')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->visible(fn (Get $get): bool => (bool) $get('assign_plan')),
                    Toggle::make('complimentary_renews')
                        ->label('Renew complimentary access automatically')
                        ->default(false)
                        ->visible(fn (Get $get): bool => (bool) $get('assign_plan')
                            && $get('subscription_funding_source') === SubscriptionFundingSource::Complimentary->value),
                ]),
            Step::make('Review')
                ->description('Confirm the sponsor account setup.')
                ->schema([
                    Placeholder::make('review')
                        ->hiddenLabel()
                        ->content(fn (Get $get): HtmlString => new HtmlString(sprintf(
                            '<div class="space-y-2"><p><strong>User:</strong> %s (%s)</p><p><strong>Workspace:</strong> %s · %s</p><p><strong>Plan:</strong> %s</p></div>',
                            e((string) $get('name')),
                            e((string) $get('email')),
                            e((string) $get('workspace_name')),
                            e(AccountTypes::tryFrom((string) $get('account_type'))?->label() ?? 'Not selected'),
                            $get('assign_plan') ? 'Assigned after confirmation' : 'Not assigned',
                        ))),
                ]),
        ];
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $administrator = Auth::user();
        abort_unless($administrator instanceof User, 403);

        $accountType = AccountTypes::from((string) $data['account_type']);
        $subscription = ($data['assign_plan'] ?? false) && filled($data['plan_id'] ?? null)
            ? new AssignWorkspaceSubscriptionData(
                workspaceId: 0,
                planId: (int) $data['plan_id'],
                fundingSource: SubscriptionFundingSource::from((string) $data['subscription_funding_source']),
                additionalCapacity: (int) ($data['additional_capacity'] ?? 0),
                complimentaryRenews: (bool) ($data['complimentary_renews'] ?? false),
            )
            : null;

        $workspace = app(ProvisionSponsorAccountAction::class)->execute(
            $administrator,
            new ProvisionSponsorAccountData(
                existingUserId: filled($data['existing_user_id'] ?? null) ? (int) $data['existing_user_id'] : null,
                name: Str::squish((string) $data['name']),
                email: Str::lower(trim((string) $data['email'])),
                phone: filled($data['phone'] ?? null) ? (string) $data['phone'] : null,
                status: Status::from((string) $data['status']),
                role: Roles::from((string) $data['role']),
                password: filled($data['password'] ?? null) ? (string) $data['password'] : null,
                emailVerified: (bool) ($data['email_verified'] ?? false),
                phoneVerified: (bool) ($data['phone_verified'] ?? false),
                accountVerified: (bool) ($data['account_verified'] ?? false),
                sendInvitation: (bool) ($data['send_invitation'] ?? false),
                workspace: new CreateWorkspaceData(
                    name: Str::squish((string) $data['workspace_name']),
                    accountType: $accountType,
                    organizationType: filled($data['organization_type'] ?? null)
                        ? InstitutionalOrganizationType::from((string) $data['organization_type'])
                        : null,
                    countryCode: filled($data['country_code'] ?? null) ? Str::upper((string) $data['country_code']) : null,
                    state: filled($data['state_code'] ?? null) ? NigeriaState::from((string) $data['state_code']) : null,
                    officialEmail: filled($data['official_email'] ?? null) ? Str::lower(trim((string) $data['official_email'])) : null,
                    officialPhone: filled($data['official_phone'] ?? null) ? (string) $data['official_phone'] : null,
                    memberPhone: filled($data['phone'] ?? null) ? (string) $data['phone'] : null,
                    memberJobTitle: filled($data['member_job_title'] ?? null) ? (string) $data['member_job_title'] : null,
                    authorizationConfirmed: (bool) ($data['authorization_confirmed'] ?? false),
                ),
                subscription: $subscription,
            ),
        );

        $this->createdWorkspaceId = (int) $workspace->getKey();

        $owner = $workspace->ownerMembership?->user;

        if (! $owner instanceof User) {
            throw new \LogicException('The provisioned workspace owner could not be resolved.');
        }

        return $owner;
    }

    protected function getRedirectUrl(): string
    {
        return $this->createdWorkspaceId === null
            ? parent::getRedirectUrl()
            : WorkspaceResource::getUrl('view', ['record' => $this->createdWorkspaceId]);
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Sponsor account provisioned';
    }
}
