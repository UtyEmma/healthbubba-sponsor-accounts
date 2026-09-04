<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateFeature(
            slug: 'scheduled-appointments',
            name: 'Instant Consultations',
            description: 'Access eligible instant consultations.',
        );
        $this->updateFeature(
            slug: 'gp-consultations',
            name: 'Scheduled Consultations',
            description: 'Shared or pooled scheduled consultation allowance.',
        );
        $this->updateFeature(
            slug: 'gp-consultations-per-seat',
            name: 'Scheduled Consultations per seat',
            description: 'Monthly scheduled consultation allowance for each employee seat.',
        );
        $this->updateFeature(
            slug: 'specialist-consultations',
            name: 'Instant Consultations',
            description: 'Shared or pooled instant consultation allowance.',
        );
        $this->updateFeature(
            slug: 'specialist-consultations-per-seat',
            name: 'Instant Consultations per seat',
            description: 'Monthly instant consultation allowance for each employee seat.',
        );
    }

    public function down(): void
    {
        $this->updateFeature(
            slug: 'scheduled-appointments',
            name: 'Scheduled Appointments',
            description: 'Book eligible consultations for a later time.',
        );
        $this->updateFeature(
            slug: 'gp-consultations',
            name: 'GP Consultations',
            description: 'Shared or pooled general practitioner consultation allowance.',
        );
        $this->updateFeature(
            slug: 'gp-consultations-per-seat',
            name: 'GP Consultations per seat',
            description: 'Monthly GP consultation allowance for each employee seat.',
        );
        $this->updateFeature(
            slug: 'specialist-consultations',
            name: 'Specialist Consultations',
            description: 'Shared or pooled specialist consultation allowance.',
        );
        $this->updateFeature(
            slug: 'specialist-consultations-per-seat',
            name: 'Specialist Consultations per seat',
            description: 'Monthly specialist consultation allowance for each employee seat.',
        );
    }

    private function updateFeature(string $slug, string $name, string $description): void
    {
        DB::table(config()->string('subscriptionify.tables.features', 'features'))
            ->where('slug', $slug)
            ->update([
                'name' => $name,
                'description' => $description,
                'updated_at' => now(),
            ]);
    }
};
