<?php

namespace App\Models;

use App\Enums\BeneficiaryRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone
 */
final class Beneficiary extends Model
{
    protected $table = 'users';

    protected $connection = 'main_sql';

    protected $guarded = ['*'];

    public $timestamps = false;

    protected static function booted(): void
    {
        self::addGlobalScope('isPatient', function (Builder $builder): void {
            $builder->where('type', BeneficiaryRoles::PATIENT);
        });
    }
}
