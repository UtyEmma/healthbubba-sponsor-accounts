<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workspaces')->select('id')->orderBy('id')->each(function (object $workspace): void {
            $members = DB::table('user_workspace')
                ->where('workspace_id', $workspace->id)
                ->orderBy('created_at')
                ->orderBy('user_id')
                ->get();

            foreach ($members as $index => $member) {
                $user = DB::table('users')->where('id', $member->user_id)->first(['name', 'email']);

                DB::table('user_workspace')->where('id', $member->id)->update([
                    'public_id' => (string) Str::ulid(),
                    'name' => $user?->name ?? 'Workspace Member',
                    'email' => Str::lower(trim($user?->email ?? "member-{$member->id}@invalid.local")),
                    'role' => $index === 0
                        ? 'owner'
                        : (in_array($member->role, ['admin', 'super_admin', 'administrator'], true) ? 'administrator' : 'viewer'),
                    'status' => $member->status === 'inactive' ? 'disabled' : 'active',
                    'accepted_at' => $member->created_at,
                    'last_selected_at' => $member->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Historical ownership cannot be reconstructed reliably after this backfill.
    }
};
