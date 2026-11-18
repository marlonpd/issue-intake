<?php

namespace Database\Factories;

use App\Enums\IssueCategory;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(IssuePriority::cases()),
            'category' => fake()->randomElement(IssueCategory::cases()),
            'status' => IssueStatus::Open,
        ];
    }
}
