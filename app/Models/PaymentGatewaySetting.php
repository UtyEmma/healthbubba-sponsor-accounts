<?php

namespace App\Models;

use App\Enums\AccountTypes;
use App\Enums\Payments\PaymentGatewayName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property PaymentGatewayName $active_gateway
 * @property PaymentGatewayName|null $individual_gateway
 * @property PaymentGatewayName|null $business_gateway
 * @property PaymentGatewayName|null $institution_gateway
 * @property int|null $updated_by_user_id
 * @property-read User|null $updatedBy
 */
final class PaymentGatewaySetting extends Model
{
    public const int SINGLETON_ID = 1;

    /** @var list<string> */
    protected $fillable = [
        'active_gateway',
        'individual_gateway',
        'business_gateway',
        'institution_gateway',
        'updated_by_user_id',
    ];

    public function gatewayFor(AccountTypes $accountType): PaymentGatewayName
    {
        return match ($accountType) {
            AccountTypes::INDIVIDUAL => $this->individual_gateway,
            AccountTypes::BUSINESS => $this->business_gateway,
            AccountTypes::INSTITUTION => $this->institution_gateway,
        } ?? $this->active_gateway;
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /** @return array<string, class-string> */
    protected function casts(): array
    {
        return [
            'active_gateway' => PaymentGatewayName::class,
            'individual_gateway' => PaymentGatewayName::class,
            'business_gateway' => PaymentGatewayName::class,
            'institution_gateway' => PaymentGatewayName::class,
        ];
    }
}
