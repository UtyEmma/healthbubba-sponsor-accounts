<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $usedSlugs = [];

        DB::table('campaigns')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($campaigns) use (&$usedSlugs): void {
                foreach ($campaigns as $campaign) {
                    $base = rtrim(Str::substr(Str::slug((string) $campaign->name), 0, 240), '-') ?: 'campaign';
                    $slug = $base;

                    if (isset($usedSlugs[$slug])) {
                        $slug = "{$base}-{$campaign->id}";
                    }

                    while (isset($usedSlugs[$slug])) {
                        $slug .= '-campaign';
                    }

                    DB::table('campaigns')
                        ->where('id', $campaign->id)
                        ->update(['slug' => $slug]);

                    $usedSlugs[$slug] = true;
                }
            });
    }

    public function down(): void
    {
        DB::table('campaigns')->update(['slug' => null]);
    }
};
