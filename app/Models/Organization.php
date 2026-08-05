<?php

namespace App\Models;

use App\Concerns\HasWallet;
use App\Enums\AccountTypes;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model {
    use HasWallet;
    
    protected $fillable = ['name', 'type', 'logo', 'description'];

    protected $casts = [
        'type' => AccountTypes::class
    ];

    function users(){
        return $this->belongsToMany(Organization::class)
                    ->withPivot('role', 'status')
                    ->withTimestamps();
    }



}
