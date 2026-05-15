<?php

namespace Database\Seeders;

use App\Enums\Plan\PlanStatusEnum;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        Plan::insert([
            ['id' => 1, 'name' => 'Plano de psicologia', 'description' => 'Plano de acompanhamento psicologico', 'price' => 100, 'status' => PlanStatusEnum::active, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Plano de emagrecimento', 'description' => 'Plano para auxiliar no processo de emagrecimento', 'price' => 150, 'status' => PlanStatusEnum::active, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Plano familiar', 'description' => 'Plano de auxilio a familia', 'price' => 200, 'status' => PlanStatusEnum::active, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
