<?php

namespace Tests\Feature;

use App\Models\Edl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EdlWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function png(bool $stroke, bool $transparent = false): string
    {
        $img = imagecreatetruecolor(200, 100);
        if ($transparent) {
            imagesavealpha($img, true);
            imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        } else {
            imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        }
        if ($stroke) {
            imagesetthickness($img, 4);
            imageline($img, 20, 20, 180, 80, imagecolorallocate($img, 0, 0, 0));
        }
        ob_start();
        imagepng($img);

        return 'data:image/png;base64,' . base64_encode((string) ob_get_clean());
    }

    private function admin(): User
    {
        return tap(User::factory()->admin()->create(), fn ($u) => $this->actingAs($u));
    }

    private function technicien(): User
    {
        return tap(User::factory()->create(), fn ($u) => $this->actingAs($u));
    }

    // ── Conflits entre appareils ────────────────────────────────

    public function test_save_survey_increments_revision_only_when_data_changes(): void
    {
        $this->admin();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"1"}', 'base_rev' => 0])
            ->assertOk()->assertJsonPath('survey_rev', 1);
        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"1"}', 'base_rev' => 1])
            ->assertOk()->assertJsonPath('survey_rev', 1);
        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"2"}', 'base_rev' => 1])
            ->assertOk()->assertJsonPath('survey_rev', 2);
    }

    public function test_stale_revision_returns_conflict_with_server_data(): void
    {
        $this->admin();
        $edl = Edl::factory()->create(['survey_data' => ['a' => 'serveur'], 'survey_rev' => 5]);

        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"moi"}', 'base_rev' => 3])
            ->assertStatus(409)
            ->assertJsonPath('conflict', true)
            ->assertJsonPath('survey_rev', 5)
            ->assertJsonPath('survey_data.a', 'serveur');

        $this->assertSame(['a' => 'serveur'], $edl->fresh()->survey_data);
    }

    public function test_forced_save_overrides_a_conflict(): void
    {
        $this->admin();
        $edl = Edl::factory()->create(['survey_data' => ['a' => 'serveur'], 'survey_rev' => 5]);

        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"moi"}', 'base_rev' => 3, 'force' => true])
            ->assertOk()->assertJsonPath('survey_rev', 6);

        $this->assertSame(['a' => 'moi'], $edl->fresh()->survey_data);
    }

    public function test_save_without_base_rev_is_still_accepted(): void
    {
        $this->admin();
        $edl = Edl::factory()->create(['survey_rev' => 4]);

        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"1"}'])->assertOk();
    }

    public function test_lone_surrogate_in_survey_is_repaired(): void
    {
        $this->admin();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"x\\ud83d"}'])->assertOk();

        $this->assertSame("x\u{FFFD}", $edl->fresh()->survey_data['a']);
    }

    // ── Signature vide ──────────────────────────────────────────

    public function test_blank_signatures_are_rejected(): void
    {
        Storage::fake('local');
        $this->admin();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/finalize", [
            'signature_technicien' => $this->png(false),
            'signature'            => $this->png(false, true),
        ])->assertStatus(422)->assertJsonValidationErrors(['signature_technicien', 'signature']);

        $this->assertSame('en_cours', $edl->fresh()->status);
    }

    public function test_non_image_signature_is_rejected(): void
    {
        $this->admin();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/finalize", [
            'signature_technicien' => 'pas une image',
            'locataire_absent'     => true,
        ])->assertStatus(422)->assertJsonValidationErrors('signature_technicien');
    }

    public function test_signature_with_a_stroke_is_accepted(): void
    {
        Storage::fake('local');
        $this->admin();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/finalize", [
            'signature_technicien' => $this->png(true),
            'signature'            => $this->png(true, true),
        ])->assertOk();
    }

    // ── Duplication ─────────────────────────────────────────────

    public function test_duplicate_copies_the_logement_for_a_new_tenant(): void
    {
        $user = $this->admin();
        $src = Edl::factory()->create([
            'type' => 'sortant', 'adresse' => '1 rue A', 'ville' => 'Lyon', 'steps' => ['entree', 'compteurs', 'synthese'],
            'survey_data' => ['x' => 'y'], 'locataire_nom' => 'Ancien', 'status' => 'complete',
            'signature' => 'sig', 'entrant_id' => null,
        ]);

        $res = $this->postJson("/api/edls/{$src->id}/duplicate", [
            'type' => 'entrant', 'keep_survey' => true, 'keep_tenant' => false,
            'technicien_prenom' => 'Jo', 'technicien_nom' => 'DUPONT', 'technicien_email' => 'jo@example.com',
        ])->assertCreated();

        $copy = Edl::findOrFail($res->json('id'));
        $this->assertSame('entrant', $copy->type);
        $this->assertSame('en_cours', $copy->status);
        $this->assertSame('1 rue A', $copy->adresse);
        $this->assertSame(['x' => 'y'], $copy->survey_data);
        $this->assertSame($src->steps, $copy->steps);
        $this->assertNull($copy->locataire_nom);
        $this->assertNull($copy->signature);
        $this->assertNull($copy->entrant_id);
        $this->assertSame($user->id, $copy->user_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'edl_duplicated', 'entity_id' => $copy->id]);
    }

    public function test_duplicate_as_sortant_links_the_original_entrant(): void
    {
        $this->admin();
        $entrant = Edl::factory()->create(['type' => 'entrant']);

        $res = $this->postJson("/api/edls/{$entrant->id}/duplicate", [
            'type' => 'sortant', 'keep_tenant' => true, 'keep_survey' => false,
            'technicien_prenom' => 'Jo', 'technicien_nom' => 'D', 'technicien_email' => 'jo@example.com',
        ])->assertCreated();

        $copy = Edl::findOrFail($res->json('id'));
        $this->assertSame($entrant->id, $copy->entrant_id);
        $this->assertSame($entrant->locataire_nom, $copy->locataire_nom);
        $this->assertNull($copy->survey_data);
    }

    public function test_technicien_can_duplicate_any_edl_into_his_own(): void
    {
        $tech = $this->technicien();
        $other = Edl::factory()->create();

        $res = $this->postJson("/api/edls/{$other->id}/duplicate", [
            'type' => 'entrant', 'technicien_prenom' => 'a', 'technicien_nom' => 'b', 'technicien_email' => 'a@b.fr',
        ])->assertCreated();

        $this->assertSame($tech->id, Edl::findOrFail($res->json('id'))->user_id);
    }

    // ── Archivage / suppression ─────────────────────────────────

    public function test_admin_archives_and_unarchives(): void
    {
        $this->admin();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/archive")->assertOk();
        $this->assertNotNull($edl->fresh()->archived_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'edl_archived', 'entity_id' => $edl->id]);

        $this->getJson('/api/edls')->assertJsonCount(0, 'data');
        $this->getJson('/api/edls?archived=1')->assertJsonCount(1, 'data');
        $this->getJson('/api/dashboard')->assertJsonPath('stats.total', 0);

        $this->deleteJson("/api/edls/{$edl->id}/archive")->assertOk();
        $this->assertNull($edl->fresh()->archived_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'edl_unarchived', 'entity_id' => $edl->id]);
        $this->getJson('/api/edls')->assertJsonCount(1, 'data');
    }

    public function test_technicien_cannot_archive_nor_see_archived(): void
    {
        $user = $this->technicien();
        $edl = Edl::factory()->create(['user_id' => $user->id, 'archived_at' => now()]);

        $this->postJson("/api/edls/{$edl->id}/archive")->assertForbidden();
        $this->deleteJson("/api/edls/{$edl->id}/archive")->assertForbidden();
        $this->getJson('/api/edls?archived=1')->assertJsonCount(0, 'data');
    }

    public function test_technicien_cannot_delete_even_his_own_draft(): void
    {
        Storage::fake('local');
        $user = $this->technicien();
        $draft = Edl::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/edls/{$draft->id}")->assertForbidden();

        $this->assertDatabaseHas('edls', ['id' => $draft->id]);
    }

    public function test_admin_can_delete_a_completed_edl(): void
    {
        Storage::fake('local');
        $this->admin();
        $done = Edl::factory()->complete()->create();

        $this->deleteJson("/api/edls/{$done->id}")->assertOk();
    }

    // ── Retenues (bilan chiffré) ────────────────────────────────

    public function test_retenues_are_saved_and_returned_by_the_comparison(): void
    {
        $this->admin();
        $entrant = Edl::factory()->create(['type' => 'entrant']);
        $sortant = Edl::factory()->create(['type' => 'sortant', 'entrant_id' => $entrant->id]);

        $this->putJson("/api/edls/{$sortant->id}/retenues", ['retenues' => [
            ['label' => 'Sol : dégradé', 'amount' => 120.5],
            ['label' => 'Peinture', 'amount' => '80'],
        ]])->assertOk()->assertJsonPath('total', 200.5);

        $this->getJson("/api/edls/{$sortant->id}/comparison")
            ->assertOk()
            ->assertJsonPath('retenues.0.label', 'Sol : dégradé')
            ->assertJsonPath('retenues.1.amount', 80);

        $this->putJson("/api/edls/{$sortant->id}/retenues", ['retenues' => []])->assertOk();
        $this->assertNull($sortant->fresh()->retenues);
    }

    public function test_retenues_validation_and_scope(): void
    {
        $this->technicien();
        $mine = Edl::factory()->create(['type' => 'sortant', 'user_id' => auth()->id()]);
        $entrant = Edl::factory()->create(['type' => 'entrant', 'user_id' => auth()->id()]);
        $other = Edl::factory()->create(['type' => 'sortant']);

        $this->putJson("/api/edls/{$mine->id}/retenues", ['retenues' => [['label' => 'x', 'amount' => -5]]])
            ->assertStatus(422)->assertJsonValidationErrors('retenues.0.amount');
        $this->putJson("/api/edls/{$entrant->id}/retenues", ['retenues' => []])->assertStatus(422);
        $this->putJson("/api/edls/{$other->id}/retenues", ['retenues' => []])->assertForbidden();
    }

    // ── Tableau de bord : rappels ───────────────────────────────

    public function test_dashboard_flags_stale_edls_in_progress(): void
    {
        $user = $this->technicien();
        $fresh = Edl::factory()->create(['user_id' => $user->id]);
        $stale = Edl::factory()->create(['user_id' => $user->id]);
        Edl::whereKey($stale->id)->update(['updated_at' => now()->subDays(5)]);

        $res = $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('stats.en_retard', 1);

        $byId = collect($res->json('en_cours'))->keyBy('id');
        $this->assertTrue($byId[$stale->id]['en_retard']);
        $this->assertFalse($byId[$fresh->id]['en_retard']);
        $this->assertSame($stale->id, $res->json('en_cours.0.id')); // les plus anciens d'abord
    }
}
