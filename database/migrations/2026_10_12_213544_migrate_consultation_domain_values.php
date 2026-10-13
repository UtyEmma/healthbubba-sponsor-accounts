<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->translateStoredTypes(
            scheduled: 'scheduled',
            instant: 'instant',
        );
        $this->renameFeaturesToScheduledAndInstant();
        $this->reclassifyLinkedConsultations();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->translateStoredTypes(
            scheduled: 'gp',
            instant: 'specialist',
        );
        $this->renameFeaturesToLegacyNames();
    }

    private function translateStoredTypes(string $scheduled, string $instant): void
    {
        DB::table('consultations')
            ->where('consultation_type', $scheduled === 'scheduled' ? 'gp' : 'scheduled')
            ->update(['consultation_type' => $scheduled]);

        DB::table('consultations')
            ->where('consultation_type', $instant === 'instant' ? 'specialist' : 'instant')
            ->update(['consultation_type' => $instant]);

        DB::table('campaign_consultation_quotas')
            ->where('consultation_type', $scheduled === 'scheduled' ? 'gp' : 'scheduled')
            ->update(['consultation_type' => $scheduled]);

        DB::table('campaign_consultation_quotas')
            ->where('consultation_type', $instant === 'instant' ? 'specialist' : 'instant')
            ->update(['consultation_type' => $instant]);

        DB::table('campaign_usage_entries')
            ->where('benefit', $scheduled === 'scheduled' ? 'gp' : 'scheduled')
            ->update(['benefit' => $scheduled]);

        DB::table('campaign_usage_entries')
            ->where('benefit', $instant === 'instant' ? 'specialist' : 'instant')
            ->update(['benefit' => $instant]);

        $featureSlugs = $scheduled === 'scheduled'
            ? $this->scheduledFeatureSlugs()
            : array_flip($this->scheduledFeatureSlugs());

        foreach ($featureSlugs as $from => $to) {
            DB::table('consultations')
                ->where('feature_slug', $from)
                ->update(['feature_slug' => $to]);
        }
    }

    private function renameFeaturesToScheduledAndInstant(): void
    {
        $this->renameFeature(
            from: 'scheduled-appointments',
            to: 'instant-consultation-access',
            name: 'Instant Consultation Access',
            description: 'Access eligible instant consultations.',
        );
        $this->renameFeature(
            from: 'gp-consultations',
            to: 'scheduled-consultations',
            name: 'Scheduled Consultations',
            description: 'Shared or pooled scheduled consultation allowance.',
        );
        $this->renameFeature(
            from: 'gp-consultations-per-seat',
            to: 'scheduled-consultations-per-seat',
            name: 'Scheduled Consultations per seat',
            description: 'Monthly scheduled consultation allowance for each employee seat.',
        );
        $this->renameFeature(
            from: 'specialist-consultations',
            to: 'instant-consultations',
            name: 'Instant Consultations',
            description: 'Shared or pooled instant consultation allowance.',
        );
        $this->renameFeature(
            from: 'specialist-consultations-per-seat',
            to: 'instant-consultations-per-seat',
            name: 'Instant Consultations per seat',
            description: 'Monthly instant consultation allowance for each employee seat.',
        );
    }

    private function renameFeaturesToLegacyNames(): void
    {
        $this->renameFeature(
            from: 'instant-consultation-access',
            to: 'scheduled-appointments',
            name: 'Instant Consultations',
            description: 'Access eligible instant consultations.',
        );
        $this->renameFeature(
            from: 'scheduled-consultations',
            to: 'gp-consultations',
            name: 'Scheduled Consultations',
            description: 'Shared or pooled scheduled consultation allowance.',
        );
        $this->renameFeature(
            from: 'scheduled-consultations-per-seat',
            to: 'gp-consultations-per-seat',
            name: 'Scheduled Consultations per seat',
            description: 'Monthly scheduled consultation allowance for each employee seat.',
        );
        $this->renameFeature(
            from: 'instant-consultations',
            to: 'specialist-consultations',
            name: 'Instant Consultations',
            description: 'Shared or pooled instant consultation allowance.',
        );
        $this->renameFeature(
            from: 'instant-consultations-per-seat',
            to: 'specialist-consultations-per-seat',
            name: 'Instant Consultations per seat',
            description: 'Monthly instant consultation allowance for each employee seat.',
        );
    }

    private function renameFeature(
        string $from,
        string $to,
        string $name,
        string $description,
    ): void {
        DB::table(config()->string('subscriptionify.tables.features', 'features'))
            ->where('slug', $from)
            ->update([
                'slug' => $to,
                'name' => $name,
                'description' => $description,
                'updated_at' => now(),
            ]);
    }

    private function reclassifyLinkedConsultations(): void
    {
        DB::table('consultations')
            ->whereNotNull('appointment_id')
            ->select(['id', 'appointment_id', 'feature_slug'])
            ->orderBy('id')
            ->chunkById(250, function (Collection $consultations): void {
                $appointments = DB::connection('main_sql')
                    ->table('appointments')
                    ->whereIn('appointment_id', $consultations->pluck('appointment_id'))
                    ->pluck('is_urgent_consultation', 'appointment_id');

                $usageEntries = DB::table('campaign_usage_entries')
                    ->whereIn(
                        'source_reference',
                        $consultations->pluck('id')->map(
                            static fn (int $id): string => "consultation:{$id}",
                        ),
                    )
                    ->select(['id', 'campaign_id', 'quantity', 'source_reference'])
                    ->get();

                $campaigns = DB::table('campaigns')
                    ->whereIn('id', $usageEntries->pluck('campaign_id')->unique())
                    ->select(['id', 'scheduled_consultation_fee', 'instant_consultation_fee'])
                    ->get()
                    ->keyBy('id');

                $usageByReference = $usageEntries->keyBy('source_reference');

                foreach ($consultations as $consultation) {
                    if (! $appointments->has($consultation->appointment_id)) {
                        continue;
                    }

                    $type = (bool) $appointments->get($consultation->appointment_id)
                        ? 'instant'
                        : 'scheduled';

                    DB::table('consultations')
                        ->where('id', $consultation->id)
                        ->update([
                            'consultation_type' => $type,
                            'feature_slug' => $this->featureSlugFor(
                                type: $type,
                                current: $consultation->feature_slug,
                            ),
                        ]);

                    $sourceReference = "consultation:{$consultation->id}";
                    $usageEntry = $usageByReference->get($sourceReference);

                    if ($usageEntry === null) {
                        continue;
                    }

                    $campaign = $campaigns->get($usageEntry->campaign_id);
                    $fee = $type === 'instant'
                        ? $campaign?->instant_consultation_fee
                        : $campaign?->scheduled_consultation_fee;

                    $values = ['benefit' => $type];

                    if ($fee !== null) {
                        $quantity = max(1, (int) ($usageEntry->quantity ?? 1));
                        $values['unit_amount'] = $fee;
                        $values['total_amount'] = round((float) $fee * $quantity, 2);
                    }

                    DB::table('campaign_usage_entries')
                        ->where('id', $usageEntry->id)
                        ->update($values);
                }
            });
    }

    private function featureSlugFor(string $type, string $current): string
    {
        if (str_starts_with($current, 'campaign-')) {
            return "campaign-{$type}-consultations";
        }

        if (str_ends_with($current, '-per-seat')) {
            return "{$type}-consultations-per-seat";
        }

        return "{$type}-consultations";
    }

    /** @return array<string, string> */
    private function scheduledFeatureSlugs(): array
    {
        return [
            'gp-consultations' => 'scheduled-consultations',
            'specialist-consultations' => 'instant-consultations',
            'gp-consultations-per-seat' => 'scheduled-consultations-per-seat',
            'specialist-consultations-per-seat' => 'instant-consultations-per-seat',
            'campaign-gp-consultations' => 'campaign-scheduled-consultations',
            'campaign-specialist-consultations' => 'campaign-instant-consultations',
        ];
    }
};
