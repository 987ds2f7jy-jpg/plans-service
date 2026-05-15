<?php

namespace App\Models;

use App\Enums\Professional\ConcilTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    protected $fillable = ['id', 'name', 'description', 'council_type'];

    protected $casts = [
        'id' => 'integer',
        'council_type' => ConcilTypeEnum::class,
    ];
}
