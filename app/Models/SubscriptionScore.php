<?php

namespace App\Models;

use App\Enums\SubscriptionScore\SubscriptionScoreStatusEnum;
use Illuminate\Database\Eloquent\Model;

class SubscriptionScore extends Model
{
    protected $fillable = ['score_id', 'subscription_id', 'status'];

    protected $casts = [
        'status' => SubscriptionScoreStatusEnum::class,
    ];

    public function score()
    {
        return $this->belongsTo(Score::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
