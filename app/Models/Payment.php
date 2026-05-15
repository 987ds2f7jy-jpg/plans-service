<?php

namespace App\Models;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'subscription_id',
        'gateway_payment_id',
        'external_id',
        'provider',
        'provider_payment_id',
        'amount',
        'payment_method',
        'status',
        'paid_at',
        'webhook_url',
        'metadata',
        'customer',
        'provider_response',
        'last_synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_method' => PaymentMethodEnum::class,
        'status' => PaymentStatusEnum::class,
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'customer' => 'array',
        'provider_response' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
