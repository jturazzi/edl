<?php

namespace Tests\Feature;

use App\Models\Edl;
use App\Models\EdlPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EdlControllerTest extends TestCase
{
    use RefreshDatabase;

    /** Signature PNG factice contenant un tracé (le serveur refuse les canevas vides). */
    protected function signature(string $variant = ''): string
    {
        $img = imagecreatetruecolor(120, 60);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imagesetthickness($img, 3);
        imageline($img, 10, 10 + strlen($variant) * 5, 110, 50, imagecolorallocate($img, 15, 23, 42));
        ob_start();
        imagepng($img);

        return 'data:image/png;base64,' . base64_encode((string) ob_get_clean());
    }

    protected function authUser(): User
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        return $user;
    }

    // ── apiIndex ────────────────────────────────────────────────

    public function test_api_index_requires_authentication(): void
    {
        $this->getJson('/api/edls')->assertStatus(401);
    }

    public function test_api_index_returns_paginated_edls(): void
    {
        $this->authUser();
        Edl::factory()->count(3)->create();

        $response = $this->getJson('/api/edls');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    // ── apiStore ────────────────────────────────────────────────

    public function test_api_store_creates_edl_with_valid_data(): void
    {
        $user = $this->authUser();

        $response = $this->postJson('/api/edls', [
            'technicien_prenom' => 'Jean',
            'technicien_nom'    => 'DUPONT',
            'technicien_email'  => 'jean@example.com',
            'adresse' => '1 rue de Paris',
            'ville'   => 'Paris',
            'type'    => 'entrant',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', 'en_cours');
        $this->assertDatabaseHas('edls', [
            'adresse' => '1 rue de Paris',
            'ville'   => 'Paris',
            'type'    => 'entrant',
            'user_id' => $user->id,
        ]);
    }

    public function test_api_store_requires_adresse_ville_and_type(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/edls', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['adresse', 'ville', 'type', 'technicien_prenom', 'technicien_nom', 'technicien_email']);
    }

    public function test_api_store_rejects_invalid_type(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/edls', [
            'technicien_prenom' => 'Jean',
            'technicien_nom'    => 'DUPONT',
            'technicien_email'  => 'jean@example.com',
            'adresse' => '1 rue de Paris',
            'ville'   => 'Paris',
            'type'    => 'invalide',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_api_store_rejects_invalid_locataire_email(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/edls', [
            'technicien_prenom' => 'Jean',
            'technicien_nom'    => 'DUPONT',
            'technicien_email'  => 'jean@example.com',
            'adresse'         => '1 rue de Paris',
            'ville'           => 'Paris',
            'type'            => 'entrant',
            'locataire_email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['locataire_email']);
    }

    // ── apiShow ─────────────────────────────────────────────────

    public function test_api_show_returns_edl_with_relations(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create();
        EdlPhoto::factory()->create(['edl_id' => $edl->id]);

        $response = $this->getJson("/api/edls/{$edl->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'photos');
    }

    public function test_api_show_returns_404_for_missing_edl(): void
    {
        $this->authUser();

        $this->getJson('/api/edls/9999')->assertStatus(404);
    }

    // ── saveSurvey ──────────────────────────────────────────────

    public function test_save_survey_persists_decoded_json(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create();

        $response = $this->postJson("/api/edls/{$edl->id}/survey", [
            'survey_data' => json_encode(['piece' => 'salon', 'etat' => 'bon']),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSame(['piece' => 'salon', 'etat' => 'bon'], $edl->fresh()->survey_data);
    }

    public function test_save_survey_rejects_malformed_json(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create();

        $response = $this->postJson("/api/edls/{$edl->id}/survey", [
            'survey_data' => '{not-valid-json',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_save_survey_requires_survey_data(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/survey", [])->assertStatus(422);
    }

    // ── uploadPhoto ─────────────────────────────────────────────

    public function test_upload_photo_stores_file_and_creates_record(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();

        $response = $this->postJson("/api/edls/{$edl->id}/photos", [
            'photo'        => UploadedFile::fake()->image('photo.jpg'),
            'question_key' => 'etat_murs',
            'room'         => 'Salon',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $photo = EdlPhoto::first();
        $this->assertNotNull($photo);
        $this->assertSame('Salon', $photo->room);
        Storage::disk('local')->assertExists($photo->photo_path);
    }

    public function test_upload_photo_rejects_non_image_file(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();

        $response = $this->postJson("/api/edls/{$edl->id}/photos", [
            'photo'        => UploadedFile::fake()->create('doc.pdf', 100),
            'question_key' => 'etat_murs',
            'room'         => 'Salon',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photo']);
    }

    public function test_upload_photo_requires_question_key_and_room(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();

        $response = $this->postJson("/api/edls/{$edl->id}/photos", [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['question_key', 'room']);
    }

    // ── listPhotos ──────────────────────────────────────────────

    public function test_list_photos_returns_photos_for_edl(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create();
        EdlPhoto::factory()->count(2)->create(['edl_id' => $edl->id]);

        $response = $this->getJson("/api/edls/{$edl->id}/photos");

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    // ── apiFinalize ─────────────────────────────────────────────

    public function test_finalize_generates_pdf_and_marks_complete(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();

        $response = $this->postJson("/api/edls/{$edl->id}/finalize", [
            'signature'            => $this->signature('a'),
            'signature_technicien' => $this->signature('b'),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'edl_id' => $edl->id]);

        $edl->refresh();
        $this->assertSame('complete', $edl->status);
        $this->assertSame($this->signature('a'), $edl->signature);
        $this->assertSame($this->signature('b'), $edl->signature_technicien);
        $this->assertFalse($edl->locataire_absent);
        $this->assertNotNull($edl->pdf_path);
        Storage::disk('local')->assertExists($edl->pdf_path);

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'edl_completed',
            'entity_type' => 'edl',
            'entity_id'   => $edl->id,
        ]);
    }

    public function test_finalize_requires_signature(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/finalize", [])->assertStatus(422);
    }

    // ── sendEmail ───────────────────────────────────────────────

    public function test_send_email_fails_when_pdf_missing(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create(['pdf_path' => null]);

        $response = $this->postJson("/api/edls/{$edl->id}/send-email", [
            'recipients' => ['test@example.com'],
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_send_email_sends_mail_to_all_recipients(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->authUser();
        $edl = Edl::factory()->create();
        Storage::disk('local')->put($edl->pdf_path = 'edl/1/fake.pdf', 'fake-pdf-content');
        $edl->save();

        $response = $this->postJson("/api/edls/{$edl->id}/send-email", [
            'recipients' => ['a@example.com', 'b@example.com'],
        ]);

        $response->assertOk();
        Mail::assertSent(\App\Mail\EdlCompleteMail::class, 2);
    }

    public function test_send_email_requires_valid_recipients(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create();

        $response = $this->postJson("/api/edls/{$edl->id}/send-email", [
            'recipients' => ['not-an-email'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['recipients.0']);
    }

    // ── showPhoto / downloadPdf / viewPdf ───────────────────────

    public function test_show_photo_returns_file_response(): void
    {
        Storage::fake('local');
        $this->authUser();
        Storage::disk('local')->put('edl/1/photos/a.jpg', 'fake-image-content');
        $photo = EdlPhoto::factory()->create(['photo_path' => 'edl/1/photos/a.jpg']);

        $response = $this->get("/edl/photos/{$photo->id}");

        $response->assertOk();
    }

    public function test_download_pdf_generates_pdf_when_missing(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create(['pdf_path' => null]);

        $response = $this->get("/edl/{$edl->id}/pdf");

        $response->assertOk();
        $this->assertNotNull($edl->fresh()->pdf_path);
    }

    public function test_view_pdf_returns_inline_content_disposition(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create(['pdf_path' => null]);

        $response = $this->get("/edl/{$edl->id}/pdf/view");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_routes_require_authentication(): void
    {
        $edl = Edl::factory()->create();

        $this->get("/edl/{$edl->id}/pdf")->assertRedirect('/login');
    }

    // ── apiDestroy ──────────────────────────────────────────────

    public function test_destroy_deletes_edl_photos_and_pdf(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create(['pdf_path' => 'edl/1/report.pdf']);
        Storage::disk('local')->put('edl/1/report.pdf', 'fake-pdf');
        $photo = EdlPhoto::factory()->create(['edl_id' => $edl->id, 'photo_path' => 'edl/1/photos/a.jpg']);
        Storage::disk('local')->put('edl/1/photos/a.jpg', 'fake-image');

        $response = $this->deleteJson("/api/edls/{$edl->id}");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('edls', ['id' => $edl->id]);
        $this->assertDatabaseMissing('edl_photos', ['id' => $photo->id]);
        Storage::disk('local')->assertMissing('edl/1/report.pdf');
        Storage::disk('local')->assertMissing('edl/1/photos/a.jpg');

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'edl_deleted',
            'entity_type' => 'edl',
            'entity_id'   => $edl->id,
        ]);
    }

    public function test_api_index_search_filters_by_text_technician_and_date(): void
    {
        $this->authUser();
        Edl::factory()->create(['adresse' => '1 rue Alpha', 'ville' => 'Nantes', 'technicien_nom' => 'MARTIN', 'locataire_nom' => 'DURAND', 'date_edl' => '2026-03-15 10:00:00']);
        Edl::factory()->create(['adresse' => '2 rue Beta', 'ville' => 'Lyon', 'technicien_nom' => 'BERNARD', 'locataire_nom' => 'PETIT', 'date_edl' => '2026-04-20 10:00:00']);

        foreach (['alpha', 'nantes', 'martin', 'durand', '15/03/2026', '03/2026'] as $q) {
            $r = $this->getJson('/api/edls?q=' . urlencode($q));
            $this->assertCount(1, $r->json('data'), $q);
            $this->assertSame('1 rue Alpha', $r->json('data.0.adresse'));
        }
        $this->assertCount(2, $this->getJson('/api/edls?q=rue')->json('data'));
        $this->assertCount(0, $this->getJson('/api/edls?q=zzz')->json('data'));
    }

    public function test_api_create_sortant_copies_entrant_data(): void
    {
        $user = $this->authUser();
        $entrant = Edl::factory()->create(['type' => 'entrant', 'adresse' => '1 rue Alpha', 'locataire_nom' => 'DURAND', 'survey_data' => ['a' => 'b'], 'status' => 'complete']);

        $r = $this->postJson("/api/edls/{$entrant->id}/sortant", [
            'technicien_prenom' => 'Jean', 'technicien_nom' => 'DUPONT', 'technicien_email' => 'jean@example.com',
        ]);

        $r->assertCreated();
        $this->assertDatabaseHas('edls', ['id' => $r->json('id'), 'type' => 'sortant', 'adresse' => '1 rue Alpha', 'locataire_nom' => 'DURAND', 'status' => 'en_cours', 'technicien_nom' => 'DUPONT', 'user_id' => $user->id]);
        $this->assertSame(['a' => 'b'], Edl::find($r->json('id'))->survey_data);

        $this->postJson("/api/edls/{$r->json('id')}/sortant", [
            'technicien_prenom' => 'Jean', 'technicien_nom' => 'DUPONT', 'technicien_email' => 'jean@example.com',
        ])->assertStatus(422);
    }

    public function test_numero_matches_id_and_is_searchable(): void
    {
        $this->authUser();
        $a = Edl::factory()->create();
        Edl::factory()->create();

        $this->assertSame('EDL-' . str_pad((string) $a->id, 6, '0', STR_PAD_LEFT), $a->numero);
        $this->assertSame($a->numero, $this->getJson("/api/edls/{$a->id}")->json('numero'));
        $data = $this->getJson('/api/edls?q=' . $a->numero)->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($a->id, $data[0]['id']);
    }

    // ── Signatures : technicien + locataire (ou locataire absent) ───

    public function test_finalize_requires_technicien_signature(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/finalize", ['signature' => $this->signature()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('signature_technicien');
    }

    public function test_finalize_requires_locataire_signature_unless_absent(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/finalize", ['signature_technicien' => $this->signature()])
            ->assertStatus(422)
            ->assertJsonValidationErrors('signature');
    }

    public function test_finalize_accepts_absent_locataire_without_signature(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/finalize", [
            'signature_technicien' => $this->signature(),
            'locataire_absent' => true,
        ])->assertOk();

        $edl->refresh();
        $this->assertSame('complete', $edl->status);
        $this->assertTrue($edl->locataire_absent);
        $this->assertNull($edl->signature);
    }

    public function test_signatures_are_never_returned_in_json(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create([
            'signature' => 'data:image/png;base64,loc',
            'signature_technicien' => $this->signature(),
        ]);

        $this->getJson("/api/edls/{$edl->id}")
            ->assertOk()
            ->assertJsonMissingPath('signature')
            ->assertJsonMissingPath('signature_technicien');
    }

    // ── Photos : légende, suppression, association à un élément ────

    public function test_upload_photo_stores_element_key_and_caption(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/photos", [
            'photo' => UploadedFile::fake()->image('mur.jpg'),
            'question_key' => 'entree_murs',
            'room' => 'entree',
            'caption' => 'Trace d\'humidité',
        ])->assertOk()->assertJsonPath('question_key', 'entree_murs')->assertJsonPath('caption', 'Trace d\'humidité');

        $this->assertDatabaseHas('edl_photos', ['edl_id' => $edl->id, 'question_key' => 'entree_murs', 'caption' => 'Trace d\'humidité']);
    }

    public function test_list_photos_exposes_question_key_and_caption(): void
    {
        $this->authUser();
        $edl = Edl::factory()->create();
        EdlPhoto::factory()->create(['edl_id' => $edl->id, 'question_key' => 'entree_sol', 'room' => 'entree', 'caption' => 'Rayure']);

        $this->getJson("/api/edls/{$edl->id}/photos")
            ->assertOk()
            ->assertJsonPath('0.question_key', 'entree_sol')
            ->assertJsonPath('0.caption', 'Rayure');
    }

    public function test_update_photo_caption(): void
    {
        $this->authUser();
        $photo = EdlPhoto::factory()->create(['edl_id' => Edl::factory()->create()->id]);

        $this->patchJson("/api/photos/{$photo->id}", ['caption' => '  Fissure  '])
            ->assertOk()->assertJsonPath('caption', 'Fissure');
        $this->assertSame('Fissure', $photo->fresh()->caption);

        $this->patchJson("/api/photos/{$photo->id}", ['caption' => ''])->assertOk();
        $this->assertNull($photo->fresh()->caption);
    }

    public function test_destroy_photo_removes_file_and_record(): void
    {
        Storage::fake('local');
        $this->authUser();
        $edl = Edl::factory()->create();
        Storage::disk('local')->put('edl/1/photos/a.jpg', 'x');
        $photo = EdlPhoto::factory()->create(['edl_id' => $edl->id, 'photo_path' => 'edl/1/photos/a.jpg']);

        $this->deleteJson("/api/photos/{$photo->id}")->assertOk();

        $this->assertDatabaseMissing('edl_photos', ['id' => $photo->id]);
        Storage::disk('local')->assertMissing('edl/1/photos/a.jpg');
    }

    public function test_photos_of_a_finalized_edl_cannot_be_modified(): void
    {
        $this->authUser();
        $photo = EdlPhoto::factory()->create(['edl_id' => Edl::factory()->complete()->create()->id]);

        $this->patchJson("/api/photos/{$photo->id}", ['caption' => 'x'])->assertStatus(422);
        $this->deleteJson("/api/photos/{$photo->id}")->assertStatus(422);
        $this->assertDatabaseHas('edl_photos', ['id' => $photo->id]);
    }

    public function test_photo_endpoints_require_authentication(): void
    {
        $photo = EdlPhoto::factory()->create();

        $this->patchJson("/api/photos/{$photo->id}", ['caption' => 'x'])->assertStatus(401);
        $this->deleteJson("/api/photos/{$photo->id}")->assertStatus(401);
    }

    // ── Sortant lié à son entrant + comparaison ────────────────────

    public function test_create_sortant_links_to_entrant(): void
    {
        $this->authUser();
        $entrant = Edl::factory()->create(['type' => 'entrant']);

        $response = $this->postJson("/api/edls/{$entrant->id}/sortant", [
            'technicien_prenom' => 'Jean', 'technicien_nom' => 'Dupont', 'technicien_email' => 'jean@example.test',
        ])->assertCreated();

        $this->assertSame($entrant->id, $response->json('entrant_id'));
    }

    public function test_show_sortant_includes_entrant(): void
    {
        $this->authUser();
        $entrant = Edl::factory()->create(['type' => 'entrant']);
        $sortant = Edl::factory()->create(['type' => 'sortant', 'entrant_id' => $entrant->id]);

        $this->getJson("/api/edls/{$sortant->id}")
            ->assertOk()
            ->assertJsonPath('entrant.id', $entrant->id)
            ->assertJsonPath('entrant.numero', $entrant->numero);
    }

    public function test_comparison_reports_degradation_missing_items_and_consumption(): void
    {
        $this->authUser();
        $entrant = Edl::factory()->create(['type' => 'entrant', 'survey_data' => [
            'entree_sol_etat' => 'bon', 'entree_murs_etat' => 'usure', 'entree_porte_palière_etat' => 'bon',
            'volets_sejour_fonctionnement' => 'oui',
            'vaisselle_bol_nb' => '6', 'compteur_eau' => '100', 'cles_total' => '3',
        ]]);
        $sortant = Edl::factory()->create(['type' => 'sortant', 'entrant_id' => $entrant->id, 'survey_data' => [
            'entree_sol_etat' => 'mauvais', 'entree_murs_etat' => 'bon', 'entree_porte_palière_etat' => 'bon',
            'volets_sejour_fonctionnement' => 'non',
            'vaisselle_bol_nb' => '4', 'compteur_eau' => '112.5', 'cles_total' => '2',
        ]]);

        $response = $this->getJson("/api/edls/{$sortant->id}/comparison")->assertOk();

        $response->assertJsonPath('summary.degrade', 2)      // Sol + Volets Séjour
            ->assertJsonPath('summary.ameliore', 1)          // Murs
            ->assertJsonPath('summary.manquant', 2);         // Bol + clés

        $rows = collect($response->json('sections'))->keyBy('key');
        $sol = collect($rows['entree']['rows'])->firstWhere('label', 'Sol');
        $this->assertSame('degrade', $sol['change']);
        $this->assertSame('Bon état', $sol['before']);
        $this->assertSame('Mauvais état', $sol['after']);
        $this->assertNull(collect($rows['entree']['rows'])->firstWhere('label', 'Porte palière'), 'Un élément inchangé ne doit pas apparaître.');

        $eau = collect($rows['compteurs']['rows'])->firstWhere('label', 'Eau (m³)');
        $this->assertSame('Consommation : 12,5', $eau['note']);
    }

    public function test_comparison_requires_sortant_with_entrant(): void
    {
        $this->authUser();
        $entrant = Edl::factory()->create(['type' => 'entrant']);
        $orphan = Edl::factory()->create(['type' => 'sortant', 'entrant_id' => null]);

        $this->getJson("/api/edls/{$entrant->id}/comparison")->assertStatus(422);
        $this->getJson("/api/edls/{$orphan->id}/comparison")->assertStatus(404);
    }

    public function test_pdf_of_a_sortant_contains_comparison_and_both_signatures(): void
    {
        Storage::fake('local');
        $this->authUser();
        $entrant = Edl::factory()->create(['type' => 'entrant', 'survey_data' => ['entree_sol_etat' => 'bon']]);
        $sortant = Edl::factory()->create(['type' => 'sortant', 'entrant_id' => $entrant->id, 'survey_data' => ['entree_sol_etat' => 'mauvais']]);

        $html = view('edl.pdf', ['edl' => $sortant->fresh(['photos'])])->render();

        $this->assertStringContainsString("Comparatif avec l'état des lieux d'entrée", $html);
        $this->assertStringContainsString($entrant->numero, $html);
        $this->assertStringContainsString('Signature du technicien', $html);
        $this->assertStringContainsString('Signature du locataire', $html);
    }
}
