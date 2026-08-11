<?php

namespace App\Models;

use App\Enums\CapacityPurchases\CapacityPaymentSource;
use App\Enums\CapacityPurchases\CapacityPurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $subscription_id
 * @property int|null $user_id
 * @property string $reference
 * @property CapacityPaymentSource $payment_source
 * @property CapacityPurchaseStatus $status
 * @property int $quantity
 * @property int $previous_capacity
 * @property int $new_capacity
 * @property int $unit_amount_minor
 * @property int $prorated_unit_amount_minor
 * @property int $amount_minor
 * @property int $renewal_amount_minor
 * @property string $currency
 * @property Carbon $term_starts_at
 * @property Carbon $term_ends_at
 * @property string|null $failure_message
 * @property Carbon|null $completed_at
 * @property-read Workspace $workspace
 * @property-read Subscription $subscription
 * @property-read User|null $initiator
 * @property-read Payment|null $payment
 */
final class CapacityPurchase extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'workspace_id',
        'subscription_id',
        'user_id',
        'reference',
        'payment_source',
        'status',
        'quantity',
        'previous_capacity',
        'new_capacity',
        'unit_amount_minor',
        'prorated_unit_amount_minor',
        'amount_minor',
        'renewal_amount_minor',
        'currency',
        'term_starts_at',
        'term_ends_at',
        'failure_message',
        'completed_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => CapacityPurchaseStatus::PENDING->value,
        'currency' => 'NGN',
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<User, $this> */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return MorphOne<Payment, $this> */
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'payment_source' => CapacityPaymentSource::class,
            'status' => CapacityPurchaseStatus::class,
            'quantity' => 'integer',
            'previous_capacity' => 'integer',
            'new_capacity' => 'integer',
            'unit_amount_minor' => 'integer',
            'prorated_unit_amount_minor' => 'integer',
            'amount_minor' => 'integer',
            'renewal_amount_minor' => 'integer',
            'term_starts_at' => 'datetime',
            'term_ends_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
