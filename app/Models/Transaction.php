<?php

namespace App\Models;

use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    protected $fillable = ['amount', 'reference', 'type', 'status', 'flow'];

    protected $casts = [
        'type' => TransactionTypes::class,
        'status' => TransactionStatus::class,
        'flow' => TransactionFlow::class,
    ];

    protected $attributes = [
        'status' => TransactionStatus::PENDING,
    ];

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
}
