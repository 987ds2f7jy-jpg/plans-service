<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionMember extends Model
{
    protected $fillable = ['subscription_id', 'external_key'];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
