<?php

namespace App\Enums;

enum AccountTypes:string {

    case INDIVIDUAL = 'individual';
    case BUSINESS = 'business';
    case INSTITUTION = 'institution';

    function label(){
        return match($this) {
            $this::INDIVIDUAL => 'Individual Sponsor',
            $this::BUSINESS => 'Business Sponsor',
            $this::INSTITUTION => 'Institutional Sponsor',
        };
    }

    static function options(){
        return [
            self::BUSINESS->name => self::BUSINESS->label(),
            self::INDIVIDUAL->name => self::INDIVIDUAL->label(),
            self::INSTITUTION->name => self::INSTITUTION->label(),
        ];
    }

}