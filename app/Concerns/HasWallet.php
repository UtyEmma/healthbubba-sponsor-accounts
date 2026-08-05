<?php
namespace App\Concerns;

use App\Models\Wallet;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasWallet {

    static function bootHasWallet(){
        static::created(function(self $model) {
            if($model->canCreateWallet()) {
                $model->wallet()->create();
            }
        });

        static::deleted(function(self $model) {
            $model->wallet()->delete();
        });
    }

    protected function canCreateWallet(){
        return true;
    }

    public function wallet(){
        return $this->morphOne(Wallet::class, 'owner');
    }

}