<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConsultationController extends Controller {
    
    function index() {
        $workspace = Workspace::current();
        // dd($workspace->appointments);
        // $beneficiaries = $workspace->patients()

        return Inertia::render('consultations/index');
    }
}
