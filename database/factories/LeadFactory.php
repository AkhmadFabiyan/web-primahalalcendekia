<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Leads\Models\Lead;
use App\Modules\Clients\Enums\ClientType;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Enums\PaymentScheme;
use App\Models\User;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'pic_name' => $this->faker->name(),
            'pic_phone' => $this->faker->phoneNumber(),
            'client_type' => ClientType::DIRECT->value,
            'client_nominal' => 15000000,
            'payment_scheme' => PaymentScheme::FULL_PAYMENT->value,
            'installment_count' => 1,
            'status' => LeadStatus::DRAFT->value,
            'marketing_id' => User::factory(),
        ];
    }
}
