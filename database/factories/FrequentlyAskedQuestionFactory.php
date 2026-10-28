<?php

namespace Database\Factories;

use App\Enums\AccountTypes;
use App\Models\FrequentlyAskedQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FrequentlyAskedQuestion>
 */
class FrequentlyAskedQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_type' => fake()->randomElement(AccountTypes::cases()),
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function forAccountType(AccountTypes $accountType): static
    {
        return $this->state(fn (): array => ['account_type' => $accountType]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
