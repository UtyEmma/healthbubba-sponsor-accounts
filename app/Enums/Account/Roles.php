<?php
namespace App\Enums\Account;

enum Roles:string {

    case USER = 'user';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';

    
}