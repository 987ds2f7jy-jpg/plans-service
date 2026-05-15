<?php

namespace Database\Seeders;

use App\Enums\Professional\ConcilTypeEnum;
use App\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        Specialization::insert([
            ['id' => 1, 'name' => 'Medicina Integrativa', 'description' => 'Abordagem medica integrativa.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Clinica Medica', 'description' => 'Atendimento clinico geral.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Medicina de Familia e Comunidade', 'description' => 'Cuidado integral da familia.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Pediatria', 'description' => 'Saude de criancas e adolescentes.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Ginecologia e Obstetricia', 'description' => 'Saude da mulher.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Dermatologia', 'description' => 'Tratamento da pele.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Endocrinologia e Metabologia', 'description' => 'Disturbios hormonais e metabolicos.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Cardiologia', 'description' => 'Doencas do coracao.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'name' => 'Psiquiatria', 'description' => 'Saude mental.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'Reumatologia', 'description' => 'Doencas inflamatorias e degenerativas.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Pneumologia', 'description' => 'Doencas respiratorias.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => 'Neurologia', 'description' => 'Sistema nervoso.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => 'Nefrologia', 'description' => 'Doencas renais.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'name' => 'Infectologia', 'description' => 'Doencas infecciosas.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'name' => 'Alergia e Imunologia', 'description' => 'Sistema imune.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'name' => 'Medicina do Trabalho', 'description' => 'Saude ocupacional.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 17, 'name' => 'Nutrologia', 'description' => 'Disturbios nutricionais.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'name' => 'Ortopedia e Traumatologia', 'description' => 'Sistema musculoesqueletico.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 19, 'name' => 'Cirurgia Geral', 'description' => 'Procedimentos cirurgicos.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'name' => 'Gastroenterologia', 'description' => 'Sistema digestivo.', 'council_type' => ConcilTypeEnum::doctor, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'name' => 'Fonoaudiologia', 'description' => 'Fonoaudiologia', 'council_type' => ConcilTypeEnum::speechTherapist, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 22, 'name' => 'Psicologia', 'description' => 'Psicologia', 'council_type' => ConcilTypeEnum::psychologist, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 23, 'name' => 'Nutricao', 'description' => 'Nutricao', 'council_type' => ConcilTypeEnum::nutritionist, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 24, 'name' => 'Educacao Fisica', 'description' => 'Educacao Fisica', 'council_type' => ConcilTypeEnum::physicalEducator, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
