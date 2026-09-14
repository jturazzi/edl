<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'action'      => $this->faker->randomElement(['edl_completed', 'edl_deleted', 'category_created', 'category_deleted']),
            'entity_type' => 'edl',
            'entity_id'   => $this->faker->numberBetween(1, 100),
            'details'     => null,
        ];
    }
}
