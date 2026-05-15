<?php

namespace App\Models;

use App\Enums\Subscription\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['plan_id', 'external_key', 'status', 'payment_verified_at'];

    protected $casts = [
        'status' => SubscriptionStatusEnum::class,
        'payment_verified_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptionScores()
    {
        return $this->hasMany(SubscriptionScore::class);
    }

    public function subscriptionMembers()
    {
        return $this->hasMany(SubscriptionMember::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
