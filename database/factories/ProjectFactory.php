<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Leads\Enums\PaymentScheme;
use App\Modules\Clients\Models\Client;
use App\Modules\Leads\Models\Lead;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Projects\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = \App\Modules\Projects\Models\Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_lead_id' => Lead::factory(),
            'client_id' => Client::factory(),
            'project_name' => $this->faker->company(),
            'service_type' => 'Sertifikasi Halal',
            'client_nominal' => 1000000,
            'partner_nominal' => 800000,
            'payment_scheme' => PaymentScheme::INSTALLMENT,
            'installment_count' => 2,
            'status' => ProjectStatus::WAITING_ACTIVATION,
        ];
    }
}
