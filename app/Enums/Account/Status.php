<?php

namespace App\Enums\Account;

enum Status:string {

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    function label(){
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }

    function color(){
        return match($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'danger',
        };
    }

    function options(){
        return [
            self::ACTIVE->value => self::ACTIVE->label(),
            self::INACTIVE->value => self::INACTIVE->label(),
        ];
    }
}