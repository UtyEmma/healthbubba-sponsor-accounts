<?php
namespace App\Abstracts;

use App\Contracts\Accounts;
use App\Enums\AccountTypes;
use App\Models\Plan;
use Illuminate\Support\Collection;

abstract class AccountProvider implements Accounts {

    protected AccountTypes $accountType;

    function plans(): Collection {
        return Plan::whereAccountType($this->accountType)->get();
    }

    function features(): Collection {
        return collect(); 
    }

}