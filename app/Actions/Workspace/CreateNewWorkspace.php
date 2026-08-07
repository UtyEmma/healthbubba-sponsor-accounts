<?php

namespace App\Actions\Organizations;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Models\User;
use Exception;

class CreateNewWorkspace {

    function execute(User $user, array $data){
        if($user->workspace()->exists()) {
            return throw new Exception("This account already belongs to an existing workspace.");
        }
        
        return $user->workspace()->create($data, [
            'role' => Roles::ADMIN->value,
            'status' => Status::ACTIVE
        ]);
    }

}