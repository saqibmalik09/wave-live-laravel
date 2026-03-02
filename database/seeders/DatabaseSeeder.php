<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Seed countries
            $this->call([
                CountrySeeder::class,
                SettingSeeder::class,
            ]);

            // Create or update admin user
            User::updateOrCreate(
                ['email' => 'admin@' . env('APP_NAME') . '.com'],
                [
                    'name' => 'Administrator',
                    'about' => 'Hey! I am using ' . env('APP_NAME') . ' app.',
                    'nick_name' => 'Admin',
                    'password' => Hash::make('password'),
                    'auth_provider' => 'email_and_password',
                    'status' => 'active',
                    'public_id' => rand(10000, 99999),
                    'country_code' => '+1',
                    'contact_number' => '1234567890',
                    'email_verified_at' => true,
                ]
            );

            DB::commit();
            $this->command->info('Database seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Seeding failed: ' . $e->getMessage());
        }
    }
}