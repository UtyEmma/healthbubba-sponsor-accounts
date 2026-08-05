<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WalletController extends Controller {
    
    function index() {
        return inertia('wallet/index');
    }
}
