<?php

namespace App\Repositories\PaymentWebhookEvent;

use App\Models\PaymentWebhookEvent;
use App\Repositories\BaseRepository;

class PaymentWebhookEventRepository extends BaseRepository implements PaymentWebhookEventRepositoryInterface
{
    public function __construct(PaymentWebhookEvent $model)
    {
        parent::__construct($model);
    }
}
