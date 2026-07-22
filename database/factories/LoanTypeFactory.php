<?php

namespace Database\Factories;

use App\Models\LoanType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanType>
 */
class LoanTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('TYPE_????'),
            'name' => fake()->words(2, true),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
