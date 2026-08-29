<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('campaigns')->orderBy('id')->each(function (object $campaign) use ($now): void {
            $year = filled($campaign->end_date) ? substr((string) $campaign->end_date, 0, 4) : $now->format('Y');
            $prefix = Str::of((string) ($campaign->location ?: $campaign->name))
                ->slug('-')
                ->upper()
                ->limit(18, '')
                ->toString();

            DB::table('campaigns')->where('id', $campaign->id)->update([
                'display_enrollment_code' => "{$prefix}-{$year}-{$campaign->id}",
            ]);

            if (! $campaign->booth_required || (int) $campaign->booth_count < 1) {
                return;
            }

            foreach (range(1, (int) $campaign->booth_count) as $position) {
                $name = trim((string) $campaign->booth_site);

                if ((int) $campaign->booth_count > 1) {
                    $name .= " {$position}";
                }

                $boothId = DB::table('campaign_booths')->insertGetId([
                    'public_id' => (string) Str::ulid(),
                    'campaign_id' => $campaign->id,
                    'workspace_id' => $campaign->workspace_id,
                    'name' => $name !== '' ? $name : "Campaign booth {$position}",
                    'site' => (string) ($campaign->booth_site ?: $campaign->location ?: 'Campaign community'),
                    'community' => (string) ($campaign->location ?: $campaign->city ?: 'Campaign community'),
                    'expected_beneficiaries' => $campaign->estimated_beneficiaries,
                    'contact_name' => (string) ($campaign->booth_contact_name ?: 'HealthBubba team'),
                    'contact_phone' => (string) ($campaign->booth_contact_phone ?: '+2340000000000'),
                    'preferred_deployment_date' => $campaign->booth_preferred_deployment_date ?: $campaign->start_date ?: $now->toDateString(),
                    'setup_fee' => $campaign->booth_setup_unit_fee ?: '0.00',
                    'monthly_fee' => $campaign->booth_monthly_unit_fee ?: '0.00',
                    'currency' => $campaign->currency ?: 'NGN',
                    'status' => $campaign->booth_deactivated_at !== null
                        ? 'inactive'
                        : ($campaign->booth_activated_at !== null ? 'active' : 'requested'),
                    'setup_paid_at' => $campaign->launched_at,
                    'activated_at' => $campaign->booth_activated_at,
                    'deactivated_at' => $campaign->booth_deactivated_at,
                    'paid_through' => $campaign->booth_last_billed_at,
                    'last_billed_at' => $campaign->booth_last_billed_at,
                    'created_at' => $campaign->created_at ?: $now,
                    'updated_at' => $now,
                ]);

                $costId = DB::table('campaign_recurring_costs')->insertGetId([
                    'campaign_id' => $campaign->id,
                    'workspace_id' => $campaign->workspace_id,
                    'campaign_booth_id' => $boothId,
                    'name' => 'Booth management & service',
                    'category' => 'booth_service',
                    'monthly_amount' => $campaign->booth_monthly_unit_fee ?: '0.00',
                    'currency' => $campaign->currency ?: 'NGN',
                    'starts_on' => $campaign->booth_activated_at ?: $campaign->booth_preferred_deployment_date ?: $campaign->start_date ?: $now->toDateString(),
                    'ends_on' => $campaign->booth_deactivated_at ?: $campaign->ended_at,
                    'is_active' => $campaign->booth_activated_at !== null
                        && $campaign->booth_deactivated_at === null
                        && $campaign->ended_at === null,
                    'deactivated_at' => $campaign->booth_deactivated_at ?: $campaign->ended_at,
                    'created_at' => $campaign->created_at ?: $now,
                    'updated_at' => $now,
                ]);

                DB::table('campaign_booth_charges')
                    ->where('campaign_id', $campaign->id)
                    ->orderBy('service_period')
                    ->each(function (object $charge) use ($boothId, $campaign, $costId, $now): void {
                        DB::table('campaign_recurring_cost_charges')->insertOrIgnore([
                            'campaign_recurring_cost_id' => $costId,
                            'campaign_id' => $campaign->id,
                            'workspace_id' => $campaign->workspace_id,
                            'service_period' => $charge->service_period,
                            'amount' => $charge->unit_fee,
                            'currency' => $charge->currency ?: 'NGN',
                            'status' => $charge->status,
                            'reference' => "LEGACY-{$charge->id}-{$boothId}",
                            'attempted_at' => $charge->attempted_at,
                            'paid_at' => $charge->paid_at,
                            'meta' => $charge->meta,
                            'created_at' => $charge->created_at ?: $now,
                            'updated_at' => $now,
                        ]);
                    });
            }
        });

        DB::table('campaign_budget_usages')->orderBy('id')->each(function (object $usage) use ($now): void {
            DB::table('campaign_usage_entries')->insertOrIgnore([
                'campaign_id' => $usage->campaign_id,
                'workspace_id' => $usage->workspace_id,
                'benefit' => $usage->category,
                'total_amount' => $usage->amount,
                'currency' => $usage->currency,
                'source' => 'legacy',
                'source_reference' => "campaign-budget-usage:{$usage->id}",
                'reference' => "USG-LEGACY-{$usage->id}",
                'occurred_at' => $usage->occurred_at,
                'meta' => $usage->meta,
                'created_at' => $usage->created_at ?: $now,
                'updated_at' => $now,
            ]);
        });

        DB::table('consultations')
            ->join('workspace_beneficiaries', 'workspace_beneficiaries.id', '=', 'consultations.workspace_beneficiary_id')
            ->join('campaigns', function ($join): void {
                $join->on('campaigns.id', '=', 'workspace_beneficiaries.relatable_id')
                    ->where('workspace_beneficiaries.relatable_type', '=', 'App\\Models\\Campaign');
            })
            ->where('consultations.status', 'confirmed')
            ->select([
                'consultations.id',
                'consultations.workspace_id',
                'consultations.workspace_beneficiary_id',
                'consultations.consultation_type',
                'consultations.confirmed_at',
                'consultations.created_at',
                'campaigns.id as campaign_id',
                'campaigns.currency',
                'campaigns.gp_fee',
                'campaigns.specialist_fee',
            ])
            ->orderBy('consultations.id')
            ->each(function (object $consultation) use ($now): void {
                $fee = $consultation->consultation_type === 'gp'
                    ? $consultation->gp_fee
                    : $consultation->specialist_fee;

                DB::table('campaign_usage_entries')->insertOrIgnore([
                    'campaign_id' => $consultation->campaign_id,
                    'workspace_id' => $consultation->workspace_id,
                    'workspace_beneficiary_id' => $consultation->workspace_beneficiary_id,
                    'benefit' => $consultation->consultation_type,
                    'quantity' => 1,
                    'unit_amount' => $fee ?: '0.00',
                    'total_amount' => $fee ?: '0.00',
                    'currency' => $consultation->currency ?: 'NGN',
                    'source' => 'provider',
                    'source_reference' => "consultation:{$consultation->id}",
                    'reference' => "USG-CONSULTATION-{$consultation->id}",
                    'occurred_at' => $consultation->confirmed_at ?: $consultation->created_at,
                    'created_at' => $consultation->created_at ?: $now,
                    'updated_at' => $now,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('campaigns')->update(['display_enrollment_code' => null]);
        DB::table('campaign_usage_entries')->delete();
        DB::table('campaign_recurring_cost_charges')->delete();
        DB::table('campaign_recurring_costs')->delete();
        DB::table('campaign_booths')->delete();
    }
};
