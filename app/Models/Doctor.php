<?php

namespace App\Models;

use App\Enums\BeneficiaryRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model
{
    protected $table = 'users';

    protected $connection = 'main_sql';

    public $timestamps = false;

    protected $fillable = ['provider_type'];

    protected static function booted(): void
    {
        static::addGlobalScope('isDoctor', function (Builder $builder) {
            $builder->where('type', BeneficiaryRoles::DOCTOR);
        });
    }

}
