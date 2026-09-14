<?php

namespace Database\Factories;

use App\Models\Edl;
use App\Models\EdlPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EdlPhoto>
 */
class EdlPhotoFactory extends Factory
{
    protected $model = EdlPhoto::class;

    public function definition(): array
    {
        return [
            'edl_id'       => Edl::factory(),
            'question_key' => $this->faker->word(),
            'room'         => $this->faker->randomElement(['Salon', 'Cuisine', 'Chambre 1', 'Salle de bain']),
            'photo_path'   => 'edl/1/photos/' . $this->faker->uuid() . '.jpg',
        ];
    }
}
