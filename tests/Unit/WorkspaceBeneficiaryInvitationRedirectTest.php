<?php

use App\Models\Workspace;
use App\Models\WorkspaceBeneficiary;
use App\Services\WorkspaceBeneficiaries\InvitationUrlService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $sqlite = config('database.connections.sqlite');
    $sqlite['database'] = ':memory:';

    config([
        'database.default' => 'mysql',
        'database.connections.mysql' => $sqlite,
        'database.connections.main_sql' => $sqlite,
        'services.healthbubba.web_url' => 'https://patients.example.test',
    ]);
    DB::purge('mysql');
    DB::purge('main_sql');

    Schema::connection('mysql')->create('workspaces', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('type');
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
    });
    Schema::connection('mysql')->create('notifications', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->string('notifiable_type');
        $table->unsignedBigInteger('notifiable_id');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
    Schema::connection('main_sql')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email')->nullable();
        $table->string('type');
    });
});

it('provides a login link after an existing patient accepts an invitation', function (): void {
    $invitation = pendingPatientInvitation('patient@example.com');
    $patientId = DB::connection('main_sql')->table('users')->insertGetId([
        'email' => 'PATIENT@example.com',
        'type' => 'patient',
    ]);

    $this->post(app(InvitationUrlService::class)->decision($invitation), [
        'decision' => 'accept',
    ])->assertRedirect(app(InvitationUrlService::class)->page($invitation->refresh()));

    expect($invitation->refresh()->beneficiary_id)->toBe($patientId)
        ->and($invitation->isActive())->toBeTrue();

    $this->get(app(InvitationUrlService::class)->page($invitation))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/workspace-beneficiary')
            ->where('patientAuthentication.url', 'https://patients.example.test/login')
            ->where('patientAuthentication.action', 'login'));
});

it('provides a registration link after a new patient accepts an invitation', function (): void {
    $invitation = pendingPatientInvitation('new-patient@example.com');

    $this->post(app(InvitationUrlService::class)->decision($invitation), [
        'decision' => 'accept',
    ])->assertRedirect(app(InvitationUrlService::class)->page($invitation->refresh()));

    expect($invitation->refresh()->beneficiary_id)->toBeNull()
        ->and($invitation->isActive())->toBeTrue();

    $this->get(app(InvitationUrlService::class)->page($invitation))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/workspace-beneficiary')
            ->where('patientAuthentication.url', 'https://patients.example.test/register')
            ->where('patientAuthentication.action', 'register'));
});

it('orders beneficiaries by the date they were added with newest first', function (): void {
    $older = pendingPatientInvitation('older@example.com');
    $newer = pendingPatientInvitation('newer@example.com');

    $older->forceFill([
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ])->saveQuietly();
    $newer->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->saveQuietly();

    expect(WorkspaceBeneficiary::query()->latestAdded()->pluck('id')->all())
        ->toBe([$newer->getKey(), $older->getKey()]);
});

function pendingPatientInvitation(string $email): WorkspaceBeneficiary
{
    $workspace = Workspace::withoutEvents(fn (): Workspace => Workspace::query()->create([
        'name' => 'Sponsor Workspace',
        'type' => 'individual',
    ]));

    return WorkspaceBeneficiary::query()->create([
        'workspace_id' => $workspace->getKey(),
        'relatable_type' => $workspace->getMorphClass(),
        'relatable_id' => $workspace->getKey(),
        'public_id' => (string) Str::ulid(),
        'first_name' => 'Invited',
        'last_name' => 'Patient',
        'email' => $email,
        'phone' => '+2348000000000',
        'status' => 'pending',
        'source' => 'manual',
        'invitation_version' => 1,
        'invited_at' => now(),
        'expires_at' => now()->addDay(),
    ]);
}
