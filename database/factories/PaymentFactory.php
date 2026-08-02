<?php

namespace Database\Factories;

use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'payment_number' => fn () => 'PAY/TEST/'.Str::upper(Str::random(12)),
            'payment_date' => now()->toDateString(),
            'amount' => fake()->numberBetween(10_000, 1_000_000),
            'payment_method' => 'TRANSFER',
            'status' => PaymentStatus::PENDING,
        ];
    }
}
