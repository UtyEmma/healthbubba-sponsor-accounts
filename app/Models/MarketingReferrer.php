<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MarketingReferrer extends Model
{
    protected $connection = 'marketing_sql';

    protected $table = 'users';

    protected $guarded = ['*'];

    protected $hidden = ['password', 'remember_token'];
}
