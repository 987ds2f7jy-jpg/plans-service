<?php

namespace Database\Seeders;

use App\Enums\ExternalAccessService\ExternalAccessServiceStatusEnum;
use App\Models\ExternalAccessService;
use Illuminate\Database\Seeder;

class ExternalAccessServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['id' => 'app_nutricao', 'description' => 'Servico externo do app de nutricao', 'status' => ExternalAccessServiceStatusEnum::enable],
            ['id' => 'app_educacao_fisica', 'description' => 'Servico externo do app de educacao fisica', 'status' => ExternalAccessServiceStatusEnum::enable],
        ];

        foreach ($services as $service) {
            ExternalAccessService::updateOrCreate(['id' => $service['id']], $service);
        }
    }
}
