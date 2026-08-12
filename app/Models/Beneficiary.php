<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Beneficiary extends Model
{
    protected $table = 'users';

    protected $connection = 'main_sql';

    protected $guarded = ['*'];

    public $timestamps = false;
}
