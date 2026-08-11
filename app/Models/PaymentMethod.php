<?php

namespace App\Models;

use App\Enums\Payments\PaymentGatewayName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property PaymentGatewayName $gateway
 * @property string $type
 * @property string $authorization_code
 * @property array<string, mixed>|null $authorization_data
 * @property string $authorization_hash
 * @property string|null $customer_code
 * @property string $email
 * @property string|null $brand
 * @property string|null $last_four
 * @property string|null $exp_month
 * @property string|null $exp_year
 * @property bool $reusable
 * @property bool $is_default
 * @property Carbon|null $last_used_at
 * @property-read Workspace $workspace
 */
final class PaymentMethod extends Model
{
    /** @var list<string> */
    protected $hidden = [
        'authorization_code',
        'authorization_data',
        'authorization_hash',
        'customer_code',
        'email',
    ];

    /** @var list<string> */
    protected $fillable = [
        'workspace_id',
        'gateway',
        'type',
        'authorization_code',
        'authorization_data',
        'authorization_hash',
        'customer_code',
        'email',
        'brand',
        'last_four',
        'exp_month',
        'exp_year',
        'reusable',
        'is_default',
        'last_used_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'type' => 'card',
        'reusable' => false,
        'is_default' => false,
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGatewayName::class,
            'authorization_code' => 'encrypted',
            'authorization_data' => 'encrypted:array',
            'customer_code' => 'encrypted',
            'email' => 'encrypted',
            'reusable' => 'boolean',
            'is_default' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }
}
