<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\WorkspaceMember;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $sqlite = config('database.connections.sqlite');
    $sqlite['database'] = ':memory:';

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite' => $sqlite,
    ]);
    DB::purge('sqlite');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('referrer_id')->nullable();
        $table->timestamp('referrer_linked_at')->nullable();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('type')->nullable();
        $table->string('role')->default('user');
        $table->string('status')->default('active');
        $table->timestamp('email_verified_at')->nullable();
        $table->timestamp('phone_verified_at')->nullable();
        $table->timestamp('account_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
    Schema::create('workspaces', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('type');
        $table->string('organization_type')->nullable();
        $table->string('country_code')->nullable();
        $table->string('state_code')->nullable();
        $table->string('official_email')->nullable();
        $table->string('official_phone')->nullable();
        $table->timestamps();
    });
    Schema::create('wallets', function (Blueprint $table): void {
        $table->id();
        $table->string('owner_type');
        $table->unsignedBigInteger('owner_id');
        $table->decimal('balance', 16, 2)->default(0);
        $table->char('currency', 3)->default('NGN');
        $table->timestamps();
    });
    Schema::create('user_workspace', function (Blueprint $table): void {
        $table->id();
        $table->char('public_id', 26);
        $table->unsignedBigInteger('workspace_id');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('invited_by_user_id')->nullable();
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

it('stores an optional normalized phone number on the sponsor and owner membership', function (): void {
    $user = app(CreateNewUser::class)->create([
        'name' => 'Amaka Eze',
        'email' => 'amaka@example.com',
        'phone' => '0801 234 5678',
        'type' => 'individual',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ]);

    expect($user->phone)->toBe('+2348012345678')
        ->and(WorkspaceMember::query()->sole()->phone)->toBe('+2348012345678');
});

it('allows sponsor setup without a phone number', function (): void {
    $user = app(CreateNewUser::class)->create([
        'name' => 'Amaka Eze',
        'email' => 'amaka@example.com',
        'type' => 'business',
        'organization_name' => 'Hope Alive Limited',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ]);

    expect($user->phone)->toBeNull()
        ->and(WorkspaceMember::query()->sole()->phone)->toBeNull();
});
