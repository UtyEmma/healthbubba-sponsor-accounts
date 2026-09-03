<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Concerns\HasRole;
use App\Concerns\HasStatus;
use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property AccountTypes|null $type
 * @property Roles $role
 * @property Status $status
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $phone_verified_at
 * @property Carbon|null $account_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'phone', 'password', 'type', 'role', 'account_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRole, HasStatus, Notifiable;

    protected $attributes = [
        'role' => Roles::USER,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Roles::class,
            'type' => AccountTypes::class,
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'account_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = ['workspace'];

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->isAdmin();
    }

    /** @return BelongsToMany<Workspace, $this> */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)
            ->withPivot(
                'id',
                'public_id',
                'name',
                'email',
                'phone',
                'job_title',
                'authorization_confirmed_at',
                'role',
                'status',
                'last_selected_at',
            )
            ->withTimestamps();
    }

    /** @return HasMany<WorkspaceMember, $this> */
    public function workspaceMemberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /** @return HasMany<AccountVerificationChallenge, $this> */
    public function verificationChallenges(): HasMany
    {
        return $this->hasMany(AccountVerificationChallenge::class);
    }

    public function routeNotificationForTermii(): ?string
    {
        return $this->phone;
    }

    public function hasVerifiedAccount(): bool
    {
        return $this->account_verified_at !== null;
    }

    /** @return HasMany<Payment, $this> */
    public function initiatedPayments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<MedicalAccessRequest, $this> */
    public function requestedMedicalAccess(): HasMany
    {
        return $this->hasMany(MedicalAccessRequest::class, 'requested_by_user_id');
    }

    public function getWorkspaceAttribute(): ?Workspace
    {
        return Workspace::currentFor($this);
    }
}
