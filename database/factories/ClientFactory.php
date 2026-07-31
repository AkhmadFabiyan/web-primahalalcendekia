<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Clients\Enums\ClientType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Clients\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = \App\Modules\Clients\Models\Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => 'PHC-HAL-' . date('Y') . '-' . $this->faker->unique()->randomNumber(4, true),
            'client_type' => ClientType::DIRECT,
            'company_name' => $this->faker->company(),
            'business_sector' => 'Makanan dan Minuman',
            'address' => $this->faker->address(),
            'pic_name' => $this->faker->name(),
            'pic_phone' => $this->faker->phoneNumber(),
            'pic_email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
