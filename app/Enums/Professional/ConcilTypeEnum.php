<?php

namespace App\Enums\Professional;

enum ConcilTypeEnum: string
{
    case doctor = medico;
    case speechTherapist = fonoaudiologo;
    case psychologist = psicologo;
    case nutritionist = nutricionista;
    case physicalEducator = educador_fisico;
}
