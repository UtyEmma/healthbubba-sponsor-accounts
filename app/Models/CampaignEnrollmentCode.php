<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property int|null $created_by_user_id
 * @property string $code
 * @property int $enrollment_limit
 * @property Carbon $expires_at
 * @property-read Campaign $campaign
 */
final class CampaignEnrollmentCode extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'campaign_id',
        'created_by_user_id',
        'code',
        'enrollment_limit',
        'expires_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'enrollment_limit' => 'integer',
            'expires_at' => 'date',
        ];
    }
}
