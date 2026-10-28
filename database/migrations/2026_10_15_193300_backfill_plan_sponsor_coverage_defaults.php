<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('plans')
            ->where('account_type', 'individual')
            ->update(['covers_sponsor_by_default' => true]);

        DB::table('plans')
            ->where('slug', 'individual-coordinated-care')
            ->update(['covers_sponsor_by_default' => false]);

        $latestUsableSubscriptionIds = DB::table('subscriptions')
            ->selectRaw('MAX(id)')
            ->where('subscribable_type', 'App\\Models\\Workspace')
            ->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('status', 'active')
                        ->where(function ($query): void {
                            $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                        });
                })->orWhere(function ($query): void {
                    $query->where('status', 'trialing')
                        ->where('trial_ends_at', '>', now());
                });
            })
            ->groupBy('subscribable_id');

        $coordinatedWorkspaceIds = DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->whereIn('subscriptions.id', $latestUsableSubscriptionIds)
            ->where('plans.slug', 'individual-coordinated-care')
            ->pluck('subscriptions.subscribable_id')
            ->unique()
            ->values();

        $coordinatedWorkspaceIds->chunk(500)->each(function ($workspaceIds): void {
            DB::table('workspace_beneficiaries')
                ->whereIn('workspace_id', $workspaceIds->all())
                ->whereNotNull('primary_sponsor_user_id')
                ->update([
                    'primary_sponsor_user_id' => null,
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')
            ->where('slug', 'individual-coordinated-care')
            ->update(['covers_sponsor_by_default' => true]);

        // Primary sponsor rows released during the forward migration are not
        // recreated here because they may have since been reused as dependant
        // coverage and must be reconciled through the application Action.
    }
};
