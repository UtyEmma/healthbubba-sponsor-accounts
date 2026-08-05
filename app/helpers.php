<?php

use App\Support\State;

if(!function_exists('state')) {
    function state(mixed $status, mixed $message = '', $data = []){
        return new State($status, $message, $data);
    }
}
