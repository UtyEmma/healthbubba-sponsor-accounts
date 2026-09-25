<?php

namespace App\Models;

use App\Enums\Payments\PaymentGatewayName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property PaymentGatewayName $active_gateway
 * @property int|null $updated_by_user_id
 * @property-read User|null $updatedBy
 */
final class PaymentGatewaySetting extends Model
{
    public const int SINGLETON_ID = 1;

    /** @var list<string> */
    protected $fillable = ['active_gateway', 'updated_by_user_id'];

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return ['active_gateway' => PaymentGatewayName::class];
    }
}
