<?php

namespace Database\Seeders;

use App\Enums\Professional\ConcilTypeEnum;
use App\Models\Score;
use Illuminate\Database\Seeder;

class ScoresSeeder extends Seeder
{
    public function run(): void
    {
        $scores = [
            ['specialization_id' => null, 'concil_type' => ConcilTypeEnum::doctor],
            ['specialization_id' => null, 'concil_type' => ConcilTypeEnum::speechTherapist],
            ['specialization_id' => null, 'concil_type' => ConcilTypeEnum::psychologist],
            ['specialization_id' => null, 'concil_type' => ConcilTypeEnum::nutritionist],
            ['specialization_id' => null, 'concil_type' => ConcilTypeEnum::physicalEducator],
        ];

        for ($id = 1; $id <= 24; $id++) {
            $concil = match (true) {
                $id <= 20 => ConcilTypeEnum::doctor,
                $id === 21 => ConcilTypeEnum::speechTherapist,
                $id === 22 => ConcilTypeEnum::psychologist,
                $id === 23 => ConcilTypeEnum::nutritionist,
                default => ConcilTypeEnum::physicalEducator,
            };

            $scores[] = ['specialization_id' => $id, 'concil_type' => $concil];
        }

        $payload = array_map(fn (array $score) => $score + ['created_at' => now(), 'updated_at' => now()], $scores);
        Score::insert($payload);
    }
}
