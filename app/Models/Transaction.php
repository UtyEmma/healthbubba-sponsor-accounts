<?php

namespace App\Models;

use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $payment_id
 * @property numeric-string $amount
 * @property string $currency
 * @property string $reference
 * @property TransactionTypes $type
 * @property TransactionStatus $status
 * @property TransactionFlow $flow
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property-read Payment|null $payment
 * @property-read Model $owner
 * @property-read Model $transactable
 */
final class Transaction extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'payment_id',
        'owner_type',
        'owner_id',
        'transactable_type',
        'transactable_id',
        'amount',
        'currency',
        'reference',
        'type',
        'status',
        'flow',
        'meta',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'currency' => 'NGN',
        'status' => TransactionStatus::PENDING->value,
    ];

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return MorphTo<Model, $this> */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'type' => TransactionTypes::class,
            'status' => TransactionStatus::class,
            'flow' => TransactionFlow::class,
            'meta' => 'array',
        ];
    }
}
