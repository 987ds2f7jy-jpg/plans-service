<?php

namespace App\Models;

use App\Enums\ExternalAccessService\ExternalAccessServiceStatusEnum;
use Illuminate\Database\Eloquent\Model;

class ExternalAccessService extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'description', 'status'];

    protected $casts = [
        'status' => ExternalAccessServiceStatusEnum::class,
    ];
}
