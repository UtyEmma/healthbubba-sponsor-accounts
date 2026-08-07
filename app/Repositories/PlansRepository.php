<?php
namespace App\Repositories;

use App\Enums\AccountTypes;
use App\Models\Plan;
use App\Models\Workspace;

class PlansRepository {

    function getWorkspacePlans(?Workspace $workspace = null) {
        $workspace ??= Workspace::current();

        return Plan::query()
                    ->active()
                    ->forAccountType($workspace->type)
                    ->with(['features' => fn ($query) => $query->orderBy('sort_order')])
                    ->orderBy('sort_order')
                    ->orderBy('id')->get();
    }

    function getPlansByAccountType(AccountTypes $accountType){
        return Plan::query()
                    ->active()->forAccountType($accountType)
                    ->orderBy('sort_order')->get();
    }

}