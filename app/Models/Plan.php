<?php

namespace App\Models;

use App\Enums\Plan\PlanStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'description', 'price', 'status'];

    protected $casts = [
        'status' => PlanStatusEnum::class,
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
