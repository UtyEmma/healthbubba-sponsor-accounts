<?php

namespace App\Models;

use App\Enums\CampaignBoothStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property int $workspace_id
 * @property string $name
 * @property string $site
 * @property string $community
 * @property int|null $expected_beneficiaries
 * @property string $contact_name
 * @property string $contact_phone
 * @property Carbon $preferred_deployment_date
 * @property numeric-string $setup_fee
 * @property numeric-string $monthly_fee
 * @property string $currency
 * @property CampaignBoothStatus $status
 * @property Carbon|null $setup_paid_at
 * @property Carbon|null $activated_at
 * @property Carbon|null $deactivated_at
 * @property Carbon|null $paid_through
 * @property Carbon|null $billing_grace_ends_on
 * @property Carbon|null $billing_suspended_at
 * @property Carbon|null $last_billed_at
 */
final class CampaignBooth extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'public_id', 'campaign_id', 'workspace_id', 'name', 'site', 'community',
        'expected_beneficiaries', 'contact_name', 'contact_phone',
        'preferred_deployment_date', 'setup_fee', 'monthly_fee', 'currency',
        'status', 'setup_reference', 'setup_paid_at', 'activated_at',
        'deactivated_at', 'paid_through', 'billing_grace_ends_on',
        'billing_suspended_at', 'last_billed_at',
    ];

    protected $attributes = ['status' => CampaignBoothStatus::Requested->value, 'currency' => 'NGN'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return HasMany<CampaignRecurringCost, $this> */
    public function recurringCosts(): HasMany
    {
        return $this->hasMany(CampaignRecurringCost::class);
    }

    /** @return HasMany<WorkspaceBeneficiary, $this> */
    public function beneficiaries(): HasMany
    {
        return $this->hasMany(WorkspaceBeneficiary::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'status' => CampaignBoothStatus::class,
            'expected_beneficiaries' => 'integer',
            'setup_fee' => 'decimal:2',
            'monthly_fee' => 'decimal:2',
            'preferred_deployment_date' => 'date',
            'setup_paid_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'paid_through' => 'date',
            'billing_grace_ends_on' => 'date',
            'billing_suspended_at' => 'datetime',
            'last_billed_at' => 'datetime',
        ];
    }
}
