<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalPlanActivation extends Model
{
    protected $fillable = [
        'subscription_id',
        'plan_id',
        'external_key',
        'plan_code',
        'external_payment_reference',
        'paid_at',
        'amount',
        'currency',
        'customer',
        'metadata',
        'activated_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'customer' => 'array',
        'metadata' => 'array',
        'activated_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
