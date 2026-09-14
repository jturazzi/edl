<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'microsoft_id' => (string) $this->faker->unique()->uuid(),
            'name'         => $this->faker->name(),
            'firstname'    => $this->faker->firstName(),
            'lastname'     => $this->faker->lastName(),
            'email'        => $this->faker->unique()->safeEmail(),
        ];
    }
}
