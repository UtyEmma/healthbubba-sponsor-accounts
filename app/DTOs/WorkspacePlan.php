<?php
namespace App\DTOs;

use App\Models\Plan;
use App\Models\Workspace;

class WorkspacePlan {

    function __construct(private Workspace $workspace, private Plan $plan) {

    }

    static function make(Workspace $workspace, Plan $plan) {
        return new self($workspace, $plan);
    }

    function planFeatures(){
        return $this->plan->features;
    }

    function workspaceFeatures() {
        
    }

    function toArray(){

    }

}