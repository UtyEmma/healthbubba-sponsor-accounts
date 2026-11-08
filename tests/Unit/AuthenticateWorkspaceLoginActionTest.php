<?php

use App\Actions\Auth\AuthenticateWorkspaceLoginAction;
use App\DTOs\Auth\AuthenticatedWorkspaceLogin;
use App\Enums\Account\Roles;
use App\Enums\AccountTypes;
use App\Enums\WorkspaceMembers\WorkspaceMemberRole;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\User;
use App\Models\Workspace;
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
    ]);
    DB::purge('mysql');

    Schema::connection('mysql')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->nullable();
        $table->string('password');
        $table->string('type')->nullable();
        $table->string('role')->default('user');
        $table->string('status')->default('active');
        $table->timestamps();
    });
    Schema::connection('mysql')->create('workspaces', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('type');
        $table->timestamps();
    });
    Schema::connection('mysql')->create('user_workspace', function (Blueprint $table): void {
        $table->id();
        $table->char('public_id', 26);
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
});

it('validates the selected account type before checking the password', function (): void {
    [$user] = loginUserWithWorkspace(AccountTypes::INDIVIDUAL);

    expect(fn () => app(AuthenticateWorkspaceLoginAction::class)->execute(
        $user->email,
        'wrong-password',
        AccountTypes::BUSINESS,
    ))->toThrow(
        ValidationException::class,
        'This email does not have active access to the selected account type.',
    );
});

it('returns a generic authentication failure for a wrong password when the account type exists', function (): void {
    [$user, $membership] = loginUserWithWorkspace(AccountTypes::BUSINESS);

    $authentication = app(AuthenticateWorkspaceLoginAction::class)->execute(
        $user->email,
        'wrong-password',
        AccountTypes::BUSINESS,
    );

    expect($authentication)->toBeNull()
        ->and($membership->refresh()->last_selected_at)->toBeNull();
});

it('authenticates and selects a matching active workspace', function (): void {
    [$user, $membership] = loginUserWithWorkspace(AccountTypes::INSTITUTION);

    $authentication = app(AuthenticateWorkspaceLoginAction::class)->execute(
        mb_strtoupper($user->email),
        'correct-password',
        AccountTypes::INSTITUTION,
    );

    expect($authentication)->toBeInstanceOf(AuthenticatedWorkspaceLogin::class)
        ->and($authentication?->user->is($user))->toBeTrue()
        ->and($authentication?->membership->is($membership))->toBeTrue()
        ->and($membership->refresh()->last_selected_at)->not->toBeNull();
});

it('returns a generic authentication failure for an unknown email', function (): void {
    $authentication = app(AuthenticateWorkspaceLoginAction::class)->execute(
        'unknown@example.com',
        'wrong-password',
        AccountTypes::INDIVIDUAL,
    );

    expect($authentication)->toBeNull();
});

it('rejects administrators from the sponsor portal after validating their credentials', function (Roles $role): void {
    $administrator = User::withoutEvents(fn (): User => User::query()->create([
        'name' => 'Portal Administrator',
        'email' => $role->value.'@example.com',
        'password' => 'correct-password',
        'role' => $role,
    ]));

    expect(fn () => app(AuthenticateWorkspaceLoginAction::class)->execute(
        $administrator->email,
        'correct-password',
        AccountTypes::INDIVIDUAL,
    ))->toThrow(
        ValidationException::class,
        'Administrators are not authorized to access the sponsor portal.',
    );
})->with([Roles::ADMIN, Roles::SUPER_ADMIN]);

it('does not disclose administrator access when the password is incorrect', function (): void {
    $administrator = User::withoutEvents(fn (): User => User::query()->create([
        'name' => 'Portal Administrator',
        'email' => 'admin@example.com',
        'password' => 'correct-password',
        'role' => Roles::ADMIN,
    ]));

    $authentication = app(AuthenticateWorkspaceLoginAction::class)->execute(
        $administrator->email,
        'wrong-password',
        AccountTypes::INDIVIDUAL,
    );

    expect($authentication)->toBeNull();
});

it('returns the unauthorized error from the sponsor login endpoint', function (): void {
    $administrator = User::withoutEvents(fn (): User => User::query()->create([
        'name' => 'Portal Administrator',
        'email' => 'admin@example.com',
        'password' => 'correct-password',
        'role' => Roles::ADMIN,
    ]));

    $this->from(route('login'))
        ->post(route('login.store'), [
            'account_type' => AccountTypes::INDIVIDUAL->value,
            'email' => $administrator->email,
            'password' => 'correct-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'authorization' => 'Administrators are not authorized to access the sponsor portal.',
        ]);

    $this->assertGuest();
});

/** @return array{User, WorkspaceMember} */
function loginUserWithWorkspace(AccountTypes $accountType): array
{
    $user = User::withoutEvents(fn (): User => User::query()->create([
        'name' => 'Login User',
        'email' => 'login@example.com',
        'password' => 'correct-password',
    ]));
    $workspace = Workspace::withoutEvents(fn (): Workspace => Workspace::query()->create([
        'name' => 'Login Workspace',
        'type' => $accountType,
    ]));
    $membership = WorkspaceMember::query()->create([
        'public_id' => (string) Str::ulid(),
        'workspace_id' => $workspace->getKey(),
        'user_id' => $user->getKey(),
        'name' => $user->name,
        'email' => $user->email,
        'role' => WorkspaceMemberRole::Owner,
        'status' => WorkspaceMemberStatus::Active,
        'accepted_at' => now(),
    ]);

    return [$user, $membership];
}
