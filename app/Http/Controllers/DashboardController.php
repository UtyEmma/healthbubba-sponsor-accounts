<?php

namespace App\Http\Controllers;

use App\Enums\AccountTypes;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller {
    
    function __invoke() {
        $workspace = Workspace::current();
        $page = match($workspace->type){
            AccountTypes::INDIVIDUAL => 'sponsor/dashboard',
            AccountTypes::BUSINESS => 'business-sponsor/dashboard',
            AccountTypes::INSTITUTION => 'institutional-sponsor/dashboard',
            default => abort(403)
        };

        return Inertia::render($page);
    }
}
