<?php

namespace Database\Factories;

use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'invoice_type' => InvoiceType::ACTIVATION,
            'billing_group_id' => (string) Str::uuid(),
            'audience' => InvoiceAudience::CLIENT,
            'sequence' => 1,
            'subtotal' => fake()->numberBetween(100_000, 10_000_000),
            'discount_total' => 0,
            'status' => InvoiceStatus::DRAFT,
        ];
    }
}
