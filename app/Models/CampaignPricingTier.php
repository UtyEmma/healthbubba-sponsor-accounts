<?php

namespace App\Models;

use Database\Factories\CampaignPricingTierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $minimum_beneficiaries
 * @property int|null $maximum_beneficiaries
 * @property numeric-string $beneficiary_unit_fee
 * @property numeric-string|null $monthly_service_fee
 * @property string $currency
 * @property bool $is_active
 * @property int $sort_order
 * @property int|null $updated_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $updatedBy
 */
final class CampaignPricingTier extends Model
{
    /** @use HasFactory<CampaignPricingTierFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'minimum_beneficiaries',
        'maximum_beneficiaries',
        'beneficiary_unit_fee',
        'monthly_service_fee',
        'currency',
        'is_active',
        'sort_order',
        'updated_by_user_id',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'currency' => 'NGN',
        'is_active' => true,
        'sort_order' => 0,
    ];

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActiveForCurrency(Builder $query, string $currency): Builder
    {
        return $query
            ->where('currency', mb_strtoupper($currency))
            ->where('is_active', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'minimum_beneficiaries' => 'integer',
            'maximum_beneficiaries' => 'integer',
            'beneficiary_unit_fee' => 'decimal:2',
            'monthly_service_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
