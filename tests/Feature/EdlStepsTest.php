<?php

namespace Tests\Feature;

use App\Models\Edl;
use App\Models\User;
use App\Services\EdlStructure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdlStepsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge([
            'adresse' => '1 rue Alpha', 'ville' => 'Nantes', 'type' => 'entrant',
            'technicien_prenom' => 'Jean', 'technicien_nom' => 'Dupont', 'technicien_email' => 'jean@example.test',
        ], $extra);
    }

    public function test_normalize_adds_required_steps_keeps_form_order_and_drops_unknown_keys(): void
    {
        $this->assertSame(
            ['compteurs', 'entree', 'sejour', 'synthese'],
            EdlStructure::normalize(['sejour', 'inconnu', 'entree'])
        );
    }

    public function test_normalize_returns_null_when_everything_is_selected_or_nothing_was_chosen(): void
    {
        $this->assertNull(EdlStructure::normalize(null));
        $this->assertNull(EdlStructure::normalize(EdlStructure::keys()));
    }

    public function test_store_persists_a_normalized_selection(): void
    {
        $this->actingAs(User::factory()->create());

        $id = $this->postJson('/api/edls', $this->payload(['steps' => ['cuisine', 'entree']]))->assertCreated()->json('id');

        $this->assertSame(['compteurs', 'entree', 'cuisine', 'synthese'], Edl::find($id)->steps);
    }

    public function test_store_without_steps_keeps_all_steps(): void
    {
        $this->actingAs(User::factory()->create());

        $id = $this->postJson('/api/edls', $this->payload())->assertCreated()->json('id');

        $this->assertNull(Edl::find($id)->steps);
    }

    public function test_store_rejects_unknown_step_keys(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/edls', $this->payload(['steps' => ['entree', 'garage']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['steps.1']);
    }

    public function test_sortant_inherits_the_entrants_steps(): void
    {
        $tech = User::factory()->create();
        $this->actingAs($tech);
        $entrant = Edl::factory()->create(['user_id' => $tech->id, 'type' => 'entrant', 'steps' => ['compteurs', 'entree', 'synthese']]);

        $id = $this->postJson("/api/edls/{$entrant->id}/sortant", [
            'technicien_prenom' => 'Jean', 'technicien_nom' => 'Dupont', 'technicien_email' => 'jean@example.test',
        ])->assertCreated()->json('id');

        $this->assertSame(['compteurs', 'entree', 'synthese'], Edl::find($id)->steps);
    }

    public function test_update_steps(): void
    {
        $tech = User::factory()->create();
        $this->actingAs($tech);
        $edl = Edl::factory()->create(['user_id' => $tech->id]);

        $this->patchJson("/api/edls/{$edl->id}/steps", ['steps' => ['wc']])
            ->assertOk()
            ->assertJsonPath('steps', ['compteurs', 'wc', 'synthese']);

        $this->patchJson("/api/edls/{$edl->id}/steps", ['steps' => EdlStructure::keys()])
            ->assertOk()
            ->assertJsonPath('steps', null);

        $this->patchJson("/api/edls/{$edl->id}/steps", ['steps' => []])->assertStatus(422);
        $this->patchJson("/api/edls/{$edl->id}/steps", ['steps' => ['garage']])->assertStatus(422);
    }

    public function test_update_steps_is_forbidden_on_someone_elses_edl(): void
    {
        $this->actingAs(User::factory()->create());
        $edl = Edl::factory()->create();

        $this->patchJson("/api/edls/{$edl->id}/steps", ['steps' => ['wc']])->assertForbidden();
    }

    public function test_show_exposes_the_steps(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $edl = Edl::factory()->create(['steps' => ['compteurs', 'entree', 'synthese']]);

        $this->getJson("/api/edls/{$edl->id}")->assertOk()->assertJsonPath('steps', ['compteurs', 'entree', 'synthese']);
    }
}
