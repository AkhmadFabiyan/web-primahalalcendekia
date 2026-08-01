<?php

namespace App\Modules\Payments\Enums;

enum ReceiptType: string
{
    case PAYMENT_RECEIPT = 'PAYMENT_RECEIPT';
    case SETTLEMENT_RECEIPT = 'SETTLEMENT_RECEIPT';
}
