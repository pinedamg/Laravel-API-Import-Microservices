<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'base_plan_id' => $this->faker->unique()->randomNumber(),
            'plan_id' => $this->faker->unique()->randomNumber(),
            'title' => $this->faker->sentence(),
            'sell_mode' => 'online',
            'starts_at' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'ends_at' => $this->faker->dateTimeBetween('+2 weeks', '+3 weeks'),
            'min_price' => $this->faker->randomFloat(2, 10, 50),
            'max_price' => $this->faker->randomFloat(2, 60, 200),
            'status' => 'active',
        ];
    }
}
