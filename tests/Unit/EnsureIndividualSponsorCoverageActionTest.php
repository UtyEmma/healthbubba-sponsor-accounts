<?php

use App\Actions\WorkspaceBeneficiaries\EnsureIndividualSponsorCoverageAction;
use App\Actions\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryAction;
use App\Actions\WorkspaceBeneficiaries\UpdateWorkspaceBeneficiaryAccessAction;
use App\Actions\Workspaces\CreateNewWorkspace;
use App\DTOs\WorkspaceBeneficiaries\InviteWorkspaceBeneficiaryData;
use App\DTOs\Workspaces\CreateWorkspaceData;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceBeneficiaries\WorkspaceBeneficiaryAccessAction;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Models\WorkspaceMember;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $sqlite = config('database.connections.sqlite');
    $sqlite['database'] = ':memory:';

    config([
        'database.default' => 'mysql',
        'database.connections.mysql' => $sqlite,
        'database.connections.main_sql' => $sqlite,
    ]);
    DB::purge('mysql');
    DB::purge('main_sql');

    Schema::connection('mysql')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->nullable();
        $table->string('password')->nullable();
        $table->string('type')->nullable();
        $table->string('role')->default('user');
        $table->string('status')->default('active');
        $table->timestamps();
    });
    Schema::connection('mysql')->create('workspaces', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('type');
        $table->string('description')->nullable();
        $table->string('logo')->nullable();
        $table->string('organization_type')->nullable();
        $table->string('country_code')->nullable();
        $table->string('state_code')->nullable();
        $table->string('official_email')->nullable();
        $table->string('official_phone')->nullable();
        $table->timestamps();
    });
    Schema::connection('mysql')->create('user_workspace', function (Blueprint $table): void {
        $table->id();
        $table->char('public_id', 26)->nullable();
        $table->foreignId('workspace_id');
        $table->foreignId('user_id')->nullable();
        $table->foreignId('invited_by_user_id')->nullable();
        $table->string('name');
        $table->string('email');
        $table->string('phone')->nullable();
        $table->string('job_title')->nullable();
        $table->timestamp('authorization_confirmed_at')->nullable();
        $table->string('role');
        $table->string('status');
        $table->unsignedInteger('invitation_version')->default(1);
        $table->timestamp('invited_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamp('accepted_at')->nullable();
        $table->timestamp('declined_at')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->timestamp('disabled_at')->nullable();
        $table->timestamp('last_selected_at')->nullable();
        $table->timestamps();
    });
    Schema::connection('mysql')->create('workspace_beneficiaries', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('workspace_id');
        $table->string('relatable_type');
        $table->unsignedBigInteger('relatable_id');
        $table->foreignId('invited_by_user_id')->nullable();
        $table->foreignId('primary_sponsor_user_id')->nullable();
        $table->unsignedBigInteger('beneficiary_id')->nullable();
        $table->char('public_id', 26)->unique();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email');
        $table->string('phone')->default('');
        $table->string('community')->nullable();
        $table->unsignedBigInteger('campaign_booth_id')->nullable();
        $table->string('department')->nullable();
        $table->string('employee_id')->nullable();
        $table->string('status');
        $table->string('source');
        $table->unsignedInteger('invitation_version')->default(1);
        $table->timestamp('invited_at');
        $table->timestamp('expires_at');
        $table->timestamp('accepted_at')->nullable();
        $table->timestamp('declined_at')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->timestamp('suspended_at')->nullable();
        $table->timestamp('revoked_at')->nullable();
        $table->timestamps();
        $table->unique(['workspace_id', 'primary_sponsor_user_id']);
        $table->unique(['workspace_id', 'relatable_type', 'relatable_id', 'email']);
    });
    Schema::connection('main_sql')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email')->nullable();
        $table->string('type');
    });
});

it('creates exactly one primary sponsor coverage row and links a matching patient', function (): void {
    [$owner, $workspace] = individualWorkspaceOwner();
    $patientId = DB::connection('main_sql')->table('users')->insertGetId([
        'email' => 'OWNER@Example.com',
        'type' => 'patient',
    ]);
    $action = app(EnsureIndividualSponsorCoverageAction::class);

    $first = $action->execute($workspace);
    $second = $action->execute($workspace);

    expect($first?->getKey())->toBe($second?->getKey())
        ->and($workspace->beneficiaryEnrollments()->count())->toBe(1)
        ->and($second?->primary_sponsor_user_id)->toBe($owner->getKey())
        ->and($second?->beneficiary_id)->toBe($patientId)
        ->and($second?->isActive())->toBeTrue();
});

it('promotes an existing self-beneficiary record without replacing it', function (): void {
    [$owner, $workspace] = individualWorkspaceOwner();
    $existing = dependantCoverage($workspace, email: 'owner@example.com');

    $coverage = app(EnsureIndividualSponsorCoverageAction::class)->execute($workspace);

    expect($coverage?->getKey())->toBe($existing->getKey())
        ->and($coverage?->primary_sponsor_user_id)->toBe($owner->getKey())
        ->and($workspace->beneficiaryEnrollments()->count())->toBe(1);
});

it('does not count primary sponsor coverage as dependant capacity', function (): void {
    [, $workspace] = individualWorkspaceOwner();
    app(EnsureIndividualSponsorCoverageAction::class)->execute($workspace);
    dependantCoverage($workspace, email: 'dependant@example.com');

    expect($workspace->beneficiaryEnrollments()->consumingCapacity()->count())->toBe(1)
        ->and($workspace->beneficiaryEnrollments()->count())->toBe(2);
});

it('does not create primary sponsor coverage for business workspaces', function (): void {
    $owner = localUser('business@example.com');
    $workspace = localWorkspace(AccountTypes::BUSINESS);
    ownerMembership($owner, $workspace);

    $coverage = app(EnsureIndividualSponsorCoverageAction::class)->execute($workspace);

    expect($coverage)->toBeNull()
        ->and($workspace->beneficiaryEnrollments()->count())->toBe(0);
});

it('creates primary sponsor coverage during new individual workspace setup', function (): void {
    $owner = localUser('new-owner@example.com');

    $workspace = Workspace::withoutEvents(fn (): Workspace => app(CreateNewWorkspace::class)->execute(
        $owner,
        new CreateWorkspaceData(name: 'Owner Home', accountType: AccountTypes::INDIVIDUAL),
    ));

    expect($workspace->beneficiaryEnrollments()->count())->toBe(1)
        ->and($workspace->beneficiaryEnrollments()->first()?->primary_sponsor_user_id)->toBe($owner->getKey());
});

it('rejects inviting the individual owner as a dependant', function (): void {
    [$owner, $workspace] = individualWorkspaceOwner();
    $invite = fn () => app(InviteWorkspaceBeneficiaryAction::class)->execute(
        $workspace,
        $owner,
        new InviteWorkspaceBeneficiaryData(
            firstName: 'Ada',
            lastName: 'Sponsor',
            email: 'owner@example.com',
            phone: '+2348000000000',
            department: null,
            employeeId: null,
            community: null,
        ),
        $workspace,
    );

    expect($invite)->toThrow(
        ValidationException::class,
        'The primary sponsor is already covered and does not use a beneficiary slot.',
    );
});

it('rejects primary sponsor beneficiary-management actions', function (): void {
    [$owner, $workspace] = individualWorkspaceOwner();
    $coverage = app(EnsureIndividualSponsorCoverageAction::class)->execute($workspace);

    $suspend = fn () => app(UpdateWorkspaceBeneficiaryAccessAction::class)->execute(
        $workspace,
        $owner,
        $coverage,
        WorkspaceBeneficiaryAccessAction::Suspend,
    );

    expect($suspend)->toThrow(
        ValidationException::class,
        'Primary sponsor coverage cannot be suspended, restored, or revoked.',
    );
});

/** @return array{User, Workspace} */
function individualWorkspaceOwner(): array
{
    $owner = localUser('owner@example.com');
    $workspace = localWorkspace(AccountTypes::INDIVIDUAL);
    ownerMembership($owner, $workspace);

    return [$owner, $workspace];
}

function localUser(string $email): User
{
    return User::query()->create([
        'name' => 'Ada Sponsor',
        'email' => $email,
        'phone' => '+2348000000000',
        'password' => 'password',
    ]);
}

function localWorkspace(AccountTypes $type): Workspace
{
    return Workspace::withoutEvents(fn (): Workspace => Workspace::query()->create([
        'name' => 'Sponsor Workspace',
        'type' => $type,
    ]));
}

function ownerMembership(User $owner, Workspace $workspace): WorkspaceMember
{
    return WorkspaceMember::query()->create([
        'public_id' => (string) Str::ulid(),
        'workspace_id' => $workspace->getKey(),
        'user_id' => $owner->getKey(),
        'name' => $owner->name,
        'email' => $owner->email,
        'phone' => $owner->phone,
        'role' => WorkspaceMemberRole::Owner,
        'status' => WorkspaceMemberStatus::Active,
        'accepted_at' => now(),
    ]);
}

function dependantCoverage(Workspace $workspace, string $email): WorkspaceBeneficiary
{
    return WorkspaceBeneficiary::query()->create([
        'workspace_id' => $workspace->getKey(),
        'relatable_type' => $workspace->getMorphClass(),
        'relatable_id' => $workspace->getKey(),
        'public_id' => (string) Str::ulid(),
        'first_name' => 'Existing',
        'last_name' => 'Beneficiary',
        'email' => $email,
        'phone' => '+2348000000001',
        'status' => 'active',
        'source' => 'manual',
        'invited_at' => now(),
        'expires_at' => now(),
        'accepted_at' => now(),
    ]);
}
