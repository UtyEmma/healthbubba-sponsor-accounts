<?php

namespace App\Models;

use App\Enums\AccountTypes;
use Database\Factories\FrequentlyAskedQuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property AccountTypes $account_type
 * @property string $question
 * @property string $answer
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class FrequentlyAskedQuestion extends Model
{
    /** @use HasFactory<FrequentlyAskedQuestionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'account_type',
        'question',
        'answer',
        'is_active',
        'sort_order',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleFor(Builder $query, AccountTypes $accountType): Builder
    {
        return $query
            ->where('account_type', $accountType)
            ->where('is_active', true);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'account_type' => AccountTypes::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
