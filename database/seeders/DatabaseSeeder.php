<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlansSeeder::class,
            ExternalAccessServicesSeeder::class,
            SpecializationSeeder::class,
            ScoresSeeder::class,
        ]);
    }
}
