<?php

namespace Database\Seeders;

use App\Enums\Account\Roles;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {
    use WithoutModelEvents;
    
    /**
     * Run the database seeds.
     */
    public function run(): void {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'admin@'.env("APP_DOMAIN"),
            'role' => Roles::SUPER_ADMIN
        ]);
    }
}
