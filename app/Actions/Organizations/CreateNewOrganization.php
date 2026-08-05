<?php

namespace App\Actions\Organizations;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Models\User;

class CreateNewOrganization {

    function execute(User $user, array $data){
        if($user->organizations()->exists()) return null;
        
        $organization = $user->organizations()->create($data, [
            'role' => Roles::ADMIN->value,
            'status' => Status::ACTIVE
        ]);

        return $organization;
    }

}