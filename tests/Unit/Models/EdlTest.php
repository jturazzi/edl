<?php

namespace Tests\Unit\Models;

use App\Models\Edl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdlTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_label_returns_french_label_for_entrant(): void
    {
        $edl = Edl::factory()->make(['type' => 'entrant']);

        $this->assertSame('État des lieux entrant', $edl->type_label);
    }

    public function test_type_label_returns_french_label_for_sortant(): void
    {
        $edl = Edl::factory()->make(['type' => 'sortant']);

        $this->assertSame('État des lieux sortant', $edl->type_label);
    }

    public function test_type_label_falls_back_to_ucfirst_for_unknown_type(): void
    {
        $edl = Edl::factory()->make(['type' => 'autre']);

        $this->assertSame('Autre', $edl->type_label);
    }

    public function test_locataire_full_name_combines_prenom_and_nom(): void
    {
        $edl = Edl::factory()->make([
            'locataire_prenom' => 'Jean',
            'locataire_nom'    => 'Dupont',
        ]);

        $this->assertSame('Jean Dupont', $edl->locataire_full_name);
    }

    public function test_locataire_full_name_trims_when_one_part_missing(): void
    {
        $edl = Edl::factory()->make([
            'locataire_prenom' => null,
            'locataire_nom'    => 'Dupont',
        ]);

        $this->assertSame('Dupont', $edl->locataire_full_name);
    }

    public function test_adresse_complete_combines_adresse_and_ville(): void
    {
        $edl = Edl::factory()->make([
            'adresse' => '12 rue des Lilas',
            'ville'   => 'Lyon',
        ]);

        $this->assertSame('12 rue des Lilas, Lyon', $edl->adresse_complete);
    }

    public function test_agent_name_returns_user_full_name_when_user_present(): void
    {
        $user = User::factory()->create(['firstname' => 'Alice', 'lastname' => 'Martin']);
        $edl  = Edl::factory()->create(['user_id' => $user->id]);

        $this->assertSame('Alice Martin', $edl->fresh()->agent_name);
    }

    public function test_agent_name_returns_default_when_no_user(): void
    {
        $edl = Edl::factory()->create(['user_id' => null]);

        $this->assertSame('Non renseigné', $edl->fresh()->agent_name);
    }

    public function test_survey_data_is_cast_to_array(): void
    {
        $edl = Edl::factory()->create(['survey_data' => ['piece' => 'salon', 'etat' => 'bon']]);

        $this->assertIsArray($edl->fresh()->survey_data);
        $this->assertSame(['piece' => 'salon', 'etat' => 'bon'], $edl->fresh()->survey_data);
    }

    public function test_has_many_photos(): void
    {
        $edl = Edl::factory()->create();
        $edl->photos()->create([
            'question_key' => 'q1',
            'room'         => 'Salon',
            'photo_path'   => 'edl/1/photos/a.jpg',
        ]);

        $this->assertCount(1, $edl->fresh()->photos);
    }
}
