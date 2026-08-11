<?php

namespace App\Models;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int|null $user_id
 * @property string|null $payable_type
 * @property int|null $payable_id
 * @property int|null $payment_method_id
 * @property PaymentPurpose $purpose
 * @property PaymentGatewayName $gateway
 * @property PaymentStatus $status
 * @property string $reference
 * @property string|null $provider_reference
 * @property int $amount_minor
 * @property string $currency
 * @property array<string, mixed> $metadata
 * @property array<string, mixed> $provider_metadata
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property Carbon|null $initialized_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $failed_at
 * @property-read Workspace $workspace
 * @property-read User|null $initiator
 * @property-read Model|null $payable
 * @property-read PaymentMethod|null $paymentMethod
 * @property-read Transaction|null $transaction
 */
final class Payment extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'workspace_id',
        'user_id',
        'payable_type',
        'payable_id',
        'payment_method_id',
        'purpose',
        'gateway',
        'status',
        'reference',
        'provider_reference',
        'amount_minor',
        'currency',
        'metadata',
        'provider_metadata',
        'failure_code',
        'failure_message',
        'initialized_at',
        'paid_at',
        'failed_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'currency' => 'NGN',
        'metadata' => '[]',
        'provider_metadata' => '[]',
        'status' => PaymentStatus::PENDING->value,
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return MorphTo<Model, $this> */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** @return HasOne<Transaction, $this> */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'purpose' => PaymentPurpose::class,
            'gateway' => PaymentGatewayName::class,
            'status' => PaymentStatus::class,
            'amount_minor' => 'integer',
            'metadata' => 'array',
            'provider_metadata' => 'array',
            'initialized_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
