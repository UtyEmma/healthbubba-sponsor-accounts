<?php

namespace App\Enums;

enum InstitutionalBeneficiaryStatus: string
{
    case Added = 'added';
    case Invited = 'invited';
    case Registered = 'registered';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
