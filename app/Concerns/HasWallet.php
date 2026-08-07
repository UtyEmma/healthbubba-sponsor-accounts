<?php

namespace App\Concerns;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Model
 */
trait HasWallet
{
    public static function bootHasWallet(): void
    {
        static::created(function (self $model): void {
            if ($model->canCreateWallet()) {
                $model->wallet()->create();
            }
        });

        static::deleted(function (self $model): void {
            $model->wallets()->delete();
        });
    }

    protected function canCreateWallet(): bool
    {
        return true;
    }

    /** @return MorphOne<Wallet, $this> */
    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /** @return MorphMany<Wallet, $this> */
    public function wallets(): MorphMany
    {
        return $this->morphMany(Wallet::class, 'owner');
    }
}
