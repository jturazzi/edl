<?php

namespace Database\Factories;

use App\Models\Edl;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Edl>
 */
class EdlFactory extends Factory
{
    protected $model = Edl::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'type'             => $this->faker->randomElement(['entrant', 'sortant']),
            'adresse'          => $this->faker->streetAddress(),
            'ville'            => $this->faker->city(),
            'survey_data'      => null,
            'signature'        => null,
            'pdf_path'         => null,
            'locataire_nom'    => $this->faker->lastName(),
            'locataire_prenom' => $this->faker->firstName(),
            'locataire_email'  => $this->faker->safeEmail(),
            'status'           => 'en_cours',
            'date_edl'         => now(),
        ];
    }

    public function complete(): static
    {
        return $this->state(fn () => [
            'status'    => 'complete',
            'signature' => 'data:image/png;base64,fakesignature',
        ]);
    }
}
