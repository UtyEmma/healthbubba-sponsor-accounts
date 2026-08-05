<?php

namespace App\Concerns;

use App\Enums\Account\Roles;
use Illuminate\Database\Eloquent\Builder;

trait HasRole {

    function isSuperAdmin(){
        return in_array($this->role, [Roles::SUPER_ADMIN]);
    }

    function isAdmin(){
        return in_array($this->role, [Roles::ADMIN, Roles::SUPER_ADMIN]);
    }

    function scopeWhereIsAdmin(Builder $query){
        $query->whereIn('role', [Roles::ADMIN, Roles::SUPER_ADMIN]);
    }
        
    function scopeWhereIsSuperAdmin(Builder $query) {
        $query->where('role', Roles::SUPER_ADMIN);
    }
}