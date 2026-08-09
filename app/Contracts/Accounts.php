<?php
namespace App\Contracts;

use Illuminate\Support\Collection;

interface Accounts {

    function features() : Collection;
    function plans(): Collection;

}