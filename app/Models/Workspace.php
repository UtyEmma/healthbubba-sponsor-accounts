<?php

namespace App\Models;

use App\Concerns\HasWallet;
use App\Enums\AccountTypes;
use App\Enums\Consultations\AllocationFallback;
use App\Enums\WorkspaceMembers\WorkspaceMemberStatus;
use App\Models\Consultations\Appointment;
use App\Models\Consultations\Consultation;
use App\Relations\WorkspacePatients;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Revoltify\Subscriptionify\Concerns\InteractsWithSubscriptions;
use Revoltify\Subscriptionify\Contracts\Subscribable;
use Revoltify\Subscriptionify\Models\Feature;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $onboarded_at
 * @property string|null $logo
 * @property AccountTypes $type
 * @property AllocationFallback|null $fallback_channel
 * @property-read EloquentCollection<int, Campaign> $campaigns
 * @property-read Campaign|null $latestCampaign
 * @property-read EloquentCollection<int, Beneficiary> $patients
 */
class Workspace extends Model implements Subscribable
{
    use HasWallet, InteractsWithSubscriptions, Notifiable;

    protected $fillable = [
        'name',
        'type',
        'logo',
        'description',
        'onboarded_at',
        'fallback_channel',
    ];

    protected $casts = [
        'type' => AccountTypes::class,
        'booth_required' => 'boolean',
        'onboarded_at' => 'datetime',
        'fallback_channel' => AllocationFallback::class,
    ];

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('id', 'public_id', 'name', 'email', 'role', 'status', 'last_selected_at')
            ->withTimestamps();
    }

    /** @return HasMany<WorkspaceMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<WorkspaceBeneficiary, $this> */
    public function workspaceBeneficiaries(): HasMany
    {
        return $this->hasMany(WorkspaceBeneficiary::class);
    }

    /** @return MorphMany<WorkspaceBeneficiary, $this> */
    public function beneficiaryEnrollments(): MorphMany
    {
        return $this->morphMany(WorkspaceBeneficiary::class, 'relatable');
    }

    /** @return HasMany<Campaign, $this> */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /** @return HasOne<Campaign, $this> */
    public function latestCampaign(): HasOne
    {
        return $this->hasOne(Campaign::class)->latestOfMany();
    }

    /** @return HasMany<MedicalAccessRequest, $this> */
    public function medicalAccessRequests(): HasMany
    {
        return $this->hasMany(MedicalAccessRequest::class);
    }

    /** @return HasMany<PaymentMethod, $this> */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /** @return MorphMany<Transaction, $this> */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'owner');
    }

    public function patients(): WorkspacePatients
    {
        return new WorkspacePatients(Beneficiary::query(), $this);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'sponsor_id');
    }

    /** @return HasMany<Consultation, $this> */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public static function current(): ?self
    {
        $user = Auth::user();

        return $user instanceof User ? self::currentFor($user) : null;
    }

    public static function currentFor(User $user): ?self
    {
        $selectedId = request()->hasSession()
            ? request()->session()->get('current_workspace_id')
            : null;

        $memberships = WorkspaceMember::query()
            ->with('workspace')
            ->whereBelongsTo($user)
            ->where('status', WorkspaceMemberStatus::Active)
            ->orderByDesc('last_selected_at')
            ->orderBy('id');

        $membership = $selectedId === null
            ? $memberships->first()
            : (clone $memberships)->where('workspace_id', $selectedId)->first() ?? $memberships->first();

        if ($membership !== null && request()->hasSession()) {
            request()->session()->put('current_workspace_id', $membership->workspace_id);
        }

        return $membership?->workspace;
    }

    public static function isCurrent(Workspace $workspace): bool
    {
        return $workspace->is(self::current());
    }

    public function provider(): string
    {
        return $this->type->provider();
    }

    /** @return EloquentCollection<int, Plan> */
    public function plans(): EloquentCollection
    {
        return Plan::with('features')->whereAccountType($this->type)->get();
    }

    /** @return Collection<int, Feature> */
    public function features(): Collection
    {
        return $this->plans()
            ->flatMap(fn (Plan $plan): EloquentCollection => $plan->features)
            ->unique('id')
            ->values();
    }
}
