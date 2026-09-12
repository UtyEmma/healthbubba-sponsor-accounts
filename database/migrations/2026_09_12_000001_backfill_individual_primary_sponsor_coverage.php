<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_workspace')
            ->join('workspaces', 'workspaces.id', '=', 'user_workspace.workspace_id')
            ->join('users', 'users.id', '=', 'user_workspace.user_id')
            ->where('workspaces.type', 'individual')
            ->where('user_workspace.role', 'owner')
            ->where('user_workspace.status', 'active')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('user_workspace as earlier_owner')
                    ->whereColumn('earlier_owner.workspace_id', 'user_workspace.workspace_id')
                    ->where('earlier_owner.role', 'owner')
                    ->where('earlier_owner.status', 'active')
                    ->whereColumn('earlier_owner.id', '<', 'user_workspace.id');
            })
            ->select([
                'workspaces.id as workspace_id',
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.phone',
            ])
            ->orderBy('workspaces.id')
            ->chunkById(200, function ($owners): void {
                $emails = $owners
                    ->pluck('email')
                    ->map(static fn (mixed $email): string => mb_strtolower(trim((string) $email)))
                    ->filter()
                    ->unique()
                    ->values();
                $patientIds = collect();

                if ($emails->isNotEmpty()) {
                    $patientIds = DB::connection('main_sql')
                        ->table('users')
                        ->where('type', 'patient')
                        ->whereIn(DB::raw('LOWER(email)'), $emails->all())
                        ->select(['id', 'email'])
                        ->get()
                        ->mapWithKeys(static fn (object $patient): array => [
                            mb_strtolower(trim((string) $patient->email)) => (int) $patient->id,
                        ]);
                }

                foreach ($owners as $owner) {
                    $email = mb_strtolower(trim((string) $owner->email));
                    $existing = DB::table('workspace_beneficiaries')
                        ->where('workspace_id', $owner->workspace_id)
                        ->where('relatable_type', 'App\\Models\\Workspace')
                        ->where('relatable_id', $owner->workspace_id)
                        ->whereRaw('LOWER(email) = ?', [$email])
                        ->orderBy('id')
                        ->first();
                    $now = now();

                    if ($existing !== null) {
                        DB::table('workspace_beneficiaries')
                            ->where('id', $existing->id)
                            ->update([
                                'primary_sponsor_user_id' => $owner->user_id,
                                'beneficiary_id' => $patientIds->get($email) ?? $existing->beneficiary_id,
                                'status' => 'active',
                                'accepted_at' => $existing->accepted_at ?? $now,
                                'declined_at' => null,
                                'cancelled_at' => null,
                                'suspended_at' => null,
                                'revoked_at' => null,
                                'updated_at' => $now,
                            ]);

                        continue;
                    }

                    [$firstName, $lastName] = $this->splitName((string) $owner->name);

                    DB::table('workspace_beneficiaries')->insert([
                        'workspace_id' => $owner->workspace_id,
                        'relatable_type' => 'App\\Models\\Workspace',
                        'relatable_id' => $owner->workspace_id,
                        'invited_by_user_id' => $owner->user_id,
                        'primary_sponsor_user_id' => $owner->user_id,
                        'beneficiary_id' => $patientIds->get($email),
                        'public_id' => (string) Str::ulid(),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => (string) ($owner->phone ?? ''),
                        'department' => null,
                        'employee_id' => null,
                        'status' => 'active',
                        'source' => 'manual',
                        'invitation_version' => 1,
                        'invited_at' => $now,
                        'expires_at' => $now,
                        'accepted_at' => $now,
                        'declined_at' => null,
                        'cancelled_at' => null,
                        'suspended_at' => null,
                        'revoked_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }, 'workspaces.id', 'workspace_id');
    }

    public function down(): void
    {
        DB::table('workspace_beneficiaries')
            ->whereNotNull('primary_sponsor_user_id')
            ->update(['primary_sponsor_user_id' => null]);
    }

    /** @return array{string, string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? 'Sponsor', $parts[1] ?? ''];
    }
};
