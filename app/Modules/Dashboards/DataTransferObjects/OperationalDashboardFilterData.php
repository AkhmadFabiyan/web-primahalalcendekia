<?php

namespace App\Modules\Dashboards\DataTransferObjects;

class OperationalDashboardFilterData
{
    public function __construct(
        public readonly ?string $period_start = null,
        public readonly ?string $period_end = null,
        public readonly ?string $service = null,
        public readonly ?string $client_type = null,
        public readonly ?string $pic_id = null,
        public readonly ?string $status = null,
        public readonly ?string $stage = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            period_start: $data['period_start'] ?? null,
            period_end: $data['period_end'] ?? null,
            service: $data['service'] ?? null,
            client_type: $data['client_type'] ?? null,
            pic_id: $data['pic_id'] ?? null,
            status: $data['status'] ?? null,
            stage: $data['stage'] ?? null,
        );
    }
}
