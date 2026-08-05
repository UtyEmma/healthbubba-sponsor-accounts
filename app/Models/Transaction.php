<?php

namespace App\Models;

use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {

    protected $fillable = ['amount', 'reference', 'type', 'status', 'flow'];

    protected $casts = [
        'type' => TransactionTypes::class,
        'status' => TransactionStatus::class,
        'flow' => TransactionFlow::class
    ];

    protected $attributes = [
        'status' => TransactionStatus::PENDING
    ];

    function owner(){   
        return $this->morphTo();
    }

    function transactable(){
        return $this->morphTo();
    }

}
