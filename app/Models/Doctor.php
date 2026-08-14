<?php

namespace App\Models;

use App\Enums\BeneficiaryRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $provider_type
 */
final class Doctor extends Model
{
    protected $table = 'users';

    protected $connection = 'main_sql';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        self::addGlobalScope('isDoctor', function (Builder $builder): void {
            $builder->where('type', BeneficiaryRoles::DOCTOR);
        });
    }
}
