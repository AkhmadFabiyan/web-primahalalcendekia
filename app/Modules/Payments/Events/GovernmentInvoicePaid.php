<?php

namespace App\Modules\Payments\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GovernmentInvoicePaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $projectId,
        public string $invoiceId,
        public string $paymentId,
        public string $verifiedByUserId
    ) {}
}
