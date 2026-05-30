<?php

namespace App\Services\Plan;

use App\Models\ExternalPlanActivation;

interface ExternalPlanActivationServiceInterface
{
    public function activate(array $data): ExternalPlanActivation;
}
