<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Concerns\HasRole;
use App\Concerns\HasWallet;
use App\Concerns\HasStatus;
use App\Enums\AccountTypes;
use App\Enums\Account\Roles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'type', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRole, HasWallet, HasStatus;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'type' => AccountTypes::class,
            'role' => Roles::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $attributes = [
        'role' => Roles::USER
    ];

    protected $with = ['wallet'];

    protected function canCreateWallet(){
        return !$this->isAdmin() && $this->type == AccountTypes::INDIVIDUAL;
    }

    function organizations(){
        return $this->belongsToMany(Organization::class)
                    ->withPivot('role', 'status')
                    ->withTimestamps();
    }

    function getOrganizationAttribute(){
        return $this->organizations()->latest()->first();
    }





}
