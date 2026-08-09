<?php
namespace App\Accounts\BusinessAccounts;

use App\Abstracts\AccountProvider;
use App\Enums\AccountTypes;
use App\Models\Plan;

class BusinessAccountProvider extends AccountProvider {

    protected AccountTypes $accountType = AccountTypes::BUSINESS;

    

}