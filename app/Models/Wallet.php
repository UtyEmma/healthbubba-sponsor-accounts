<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property numeric-string $balance
 * @property string $currency
 * @property-read Model $owner
 */
final class Wallet extends Model
{
    /** @var list<string> */
    protected $fillable = ['balance', 'currency'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'balance' => '0.00',
        'currency' => 'NGN',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
