<?php

namespace App\Enums\Payment;

enum PaymentStatusEnum: string
{
    case pending = 'pending';
    case approved = 'approved';
    case rejected = 'rejected';
    case cancelled = 'cancelled';
}
