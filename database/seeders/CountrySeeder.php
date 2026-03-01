<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'United States', 'code' => '+1', 'flag' => '/settings/flags/united-states.png'],
            ['name' => 'United Kingdom', 'code' => '+44', 'flag' => '/settings/flags/united-kingdom.png'],
            ['name' => 'India', 'code' => '+91', 'flag' => '/settings/flags/india.png'],
            ['name' => 'Japan', 'code' => '+81', 'flag' => '/settings/flags/japan.png'],
            ['name' => 'China', 'code' => '+86', 'flag' => '/settings/flags/china.png'],
            ['name' => 'Pakistan', 'code' => '+92', 'flag' => '/settings/flags/pakistan.png'],
            ['name' => 'Bangladesh', 'code' => '+880', 'flag' => '/settings/flags/bangladesh.png'],
            ['name' => 'Philippines', 'code' => '+63', 'flag' => '/settings/flags/philippines.png'],
        ];

        try {
            DB::beginTransaction();

            foreach ($countries as $country) {
                // create or update by unique 'code' or 'name'
                Country::updateOrCreate(
                    ['code' => $country['code']],
                    [
                        'name' => $country['name'],
                        'flag' => $country['flag']
                    ]
                );
            }

            DB::commit();
            $this->command->info('Countries seeded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding countries: ' . $e->getMessage());
        }
    }
}