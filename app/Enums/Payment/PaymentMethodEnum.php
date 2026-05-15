<?php

namespace App\Enums\Payment;

enum PaymentMethodEnum: string
{
    case pix = 'pix';
    case creditCard = 'credit_card';
}
