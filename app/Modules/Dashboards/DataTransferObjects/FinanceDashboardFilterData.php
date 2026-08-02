<?php

namespace App\Modules\Dashboards\DataTransferObjects;

use Illuminate\Support\Carbon;

class FinanceDashboardFilterData
{
    public function __construct(
        public ?Carbon $period_start,
        public ?Carbon $period_end,
        public ?string $service,
        public ?string $client_type,
        public ?string $pic_id,
        public ?string $status,
        public ?string $stage,
        public ?string $audience,
        public ?string $invoice_type,
        public ?string $invoice_status,
        public ?string $payment_method
    ) {}

    public static function fromArray(?array $data = null): self
    {
        $data ??= [];

        return new self(
            period_start: ! empty($data['period_start']) ? Carbon::parse($data['period_start'])->startOfDay() : Carbon::now()->startOfMonth(),
            period_end: ! empty($data['period_end']) ? Carbon::parse($data['period_end'])->endOfDay() : Carbon::now()->endOfDay(),
            service: $data['service'] ?? null,
            client_type: $data['client_type'] ?? null,
            pic_id: $data['pic_id'] ?? null,
            status: $data['status'] ?? null,
            stage: $data['stage'] ?? null,
            audience: $data['audience'] ?? null,
            invoice_type: $data['invoice_type'] ?? null,
            invoice_status: $data['invoice_status'] ?? null,
            payment_method: $data['payment_method'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'service' => $this->service,
            'client_type' => $this->client_type,
            'pic_id' => $this->pic_id,
            'status' => $this->status,
            'stage' => $this->stage,
            'audience' => $this->audience,
            'invoice_type' => $this->invoice_type,
            'invoice_status' => $this->invoice_status,
            'payment_method' => $this->payment_method,
        ];
    }
}
