<?php

namespace App\Models;

use App\Enums\Professional\ConcilTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $fillable = ['specialization_id', 'concil_type'];

    protected $casts = [
        'concil_type' => ConcilTypeEnum::class,
    ];

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }
}
