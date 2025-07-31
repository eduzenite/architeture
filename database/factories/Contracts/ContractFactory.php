<?php

namespace Database\Factories\Contracts;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contracts\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'started_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'ended_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'canceled_at' => null,
        ];
    }
}
