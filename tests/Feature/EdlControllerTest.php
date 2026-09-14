<?php

namespace Tests\Feature;

use App\Models\Category;
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

    protected function authUser(): User
    {
        $user = User::factory()->create();
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

    public function test_api_index_filters_by_category_id(): void
    {
        $this->authUser();
        $category = Category::factory()->create();
        Edl::factory()->create(['category_id' => $category->id]);
        Edl::factory()->create(['category_id' => null]);

        $response = $this->getJson('/api/edls?category_id=' . $category->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    // ── apiStore ────────────────────────────────────────────────

    public function test_api_store_creates_edl_with_valid_data(): void
    {
        $user = $this->authUser();

        $response = $this->postJson('/api/edls', [
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
        $response->assertJsonValidationErrors(['adresse', 'ville', 'type']);
    }

    public function test_api_store_rejects_invalid_type(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/edls', [
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
            'adresse'         => '1 rue de Paris',
            'ville'           => 'Paris',
            'type'            => 'entrant',
            'locataire_email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['locataire_email']);
    }

    public function test_api_store_rejects_unknown_category_id(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/edls', [
            'adresse'     => '1 rue de Paris',
            'ville'       => 'Paris',
            'type'        => 'entrant',
            'category_id' => 9999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
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
            'signature' => 'data:image/png;base64,abc123',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'edl_id' => $edl->id]);

        $edl->refresh();
        $this->assertSame('complete', $edl->status);
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
}
