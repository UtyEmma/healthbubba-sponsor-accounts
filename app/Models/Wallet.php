<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model {
    
    protected $fillable = ['balance'];

    protected $attributes = [
        'balance' => 0.00
    ];

    function owner(){
        return $this->morphTo();
    }

}
