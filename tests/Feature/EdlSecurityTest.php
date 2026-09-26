<?php

namespace Tests\Feature;

use App\Models\Edl;
use App\Models\EdlPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class EdlSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return tap(User::factory()->admin()->create(), fn ($u) => $this->actingAs($u));
    }

    private function signature(): string
    {
        $img = imagecreatetruecolor(100, 50);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imageline($img, 5, 5, 95, 45, imagecolorallocate($img, 0, 0, 0));
        ob_start();
        imagepng($img);

        return 'data:image/png;base64,' . base64_encode((string) ob_get_clean());
    }

    // ── Verrouillage d'un EDL signé ─────────────────────────────

    public function test_completed_edl_rejects_every_modification(): void
    {
        Storage::fake('local');
        $this->admin();
        $edl = Edl::factory()->complete()->create(['survey_data' => ['a' => '1']]);

        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"2"}'])->assertStatus(422);
        $this->patchJson("/api/edls/{$edl->id}/steps", ['steps' => ['entree']])->assertStatus(422);
        $this->postJson("/api/edls/{$edl->id}/photos", ['photo' => UploadedFile::fake()->image('p.jpg'), 'question_key' => 'entree', 'room' => 'entree'])->assertStatus(422);
        $this->postJson("/api/edls/{$edl->id}/finalize", ['signature_technicien' => $this->signature(), 'locataire_absent' => true])->assertStatus(422);

        $this->assertSame(['a' => '1'], $edl->fresh()->survey_data);
        $this->assertSame(0, EdlPhoto::count());
    }

    public function test_finalize_records_signature_time_and_pdf_hash(): void
    {
        Storage::fake('local');
        $this->admin();
        $edl = Edl::factory()->create();

        $this->postJson("/api/edls/{$edl->id}/finalize", ['signature_technicien' => $this->signature(), 'locataire_absent' => true])->assertOk();

        $edl->refresh();
        $this->assertNotNull($edl->signed_at);
        $this->assertSame(64, strlen((string) $edl->pdf_hash));
        $this->assertSame(hash('sha256', Storage::disk('local')->get($edl->pdf_path)), $edl->pdf_hash);
    }

    public function test_integrity_check_detects_a_modified_pdf(): void
    {
        Storage::fake('local');
        $this->admin();
        $edl = Edl::factory()->create();
        $this->postJson("/api/edls/{$edl->id}/finalize", ['signature_technicien' => $this->signature(), 'locataire_absent' => true])->assertOk();

        $this->getJson("/api/edls/{$edl->id}/integrity")->assertOk()->assertJsonPath('valid', true);

        Storage::disk('local')->put($edl->fresh()->pdf_path, 'falsifié');

        $this->getJson("/api/edls/{$edl->id}/integrity")->assertOk()->assertJsonPath('valid', false);
    }

    public function test_integrity_without_recorded_hash_is_unknown(): void
    {
        Storage::fake('local');
        $this->admin();
        $edl = Edl::factory()->complete()->create(['pdf_path' => 'edl/x.pdf', 'pdf_hash' => null]);
        Storage::disk('local')->put('edl/x.pdf', 'pdf');

        $this->getJson("/api/edls/{$edl->id}/integrity")->assertOk()->assertJsonPath('valid', null);
    }

    public function test_integrity_requires_a_completed_edl(): void
    {
        $this->actingAs(User::factory()->create());
        $mine = Edl::factory()->create(['user_id' => auth()->id()]);
        $other = Edl::factory()->complete()->create();

        $this->getJson("/api/edls/{$mine->id}/integrity")->assertStatus(422);
        $this->getJson("/api/edls/{$other->id}/integrity")->assertOk(); // consultation ouverte à tous
    }

    public function test_pdf_shows_exact_signature_time_and_name(): void
    {
        $edl = Edl::factory()->complete()->create([
            'signature_technicien' => $this->signature(), 'signature' => $this->signature(),
            'locataire_prenom' => 'Paul', 'locataire_nom' => 'Martin', 'technicien_prenom' => 'Jo', 'technicien_nom' => 'DUPONT',
            'signed_at' => '2026-03-04 14:35:00', 'updated_at' => '2026-05-06 09:00:00',
        ]);

        $html = view('edl.pdf', ['edl' => $edl->fresh(['photos'])])->render();

        $this->assertStringContainsString('04/03/2026 à ', $html);
        $this->assertStringNotContainsString('06/05/2026', $html);
        $this->assertStringContainsString('Paul Martin', $html);
        $this->assertStringContainsString('Jo DUPONT', $html);
    }

    // ── Historique d'un logement ────────────────────────────────

    public function test_logement_lists_every_edl_of_the_same_address(): void
    {
        $user = $this->admin();
        $a = Edl::factory()->create(['adresse' => '12 Rue des Lilas', 'ville' => '42000 Saint-Étienne', 'date_edl' => now()->subYear()]);
        $b = Edl::factory()->create(['adresse' => ' 12 rue des lilas ', 'ville' => '42000 saint-étienne', 'date_edl' => now()]);
        Edl::factory()->create(['adresse' => '14 Rue des Lilas', 'ville' => '42000 Saint-Étienne']);
        Edl::factory()->create(['adresse' => '12 Rue des Lilas', 'ville' => '42000 Saint-Étienne', 'archived_at' => now()]);

        $res = $this->getJson('/api/logement/edls?' . http_build_query(['adresse' => '12 Rue des Lilas', 'ville' => '42000 Saint-Étienne']))->assertOk();

        $this->assertSame([$b->id, $a->id], collect($res->json('edls'))->pluck('id')->all());
        $this->assertNotNull($user);
    }

    public function test_logement_shows_every_edl_to_a_technicien(): void
    {
        $this->actingAs(User::factory()->create());
        $a = Edl::factory()->create(['adresse' => '1 rue A', 'ville' => 'Lyon']);
        $b = Edl::factory()->create(['adresse' => '1 rue A', 'ville' => 'Lyon']);

        $res = $this->getJson('/api/logement/edls?adresse=1 rue A&ville=Lyon')->assertOk();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], collect($res->json('edls'))->pluck('id')->all());
        $this->getJson('/api/logement/edls')->assertStatus(422);
    }

    // ── Limitation et journal de connexion ──────────────────────

    public function test_finalize_is_rate_limited(): void
    {
        Storage::fake('local');
        $this->admin();
        $edl = Edl::factory()->create();
        $payload = ['signature_technicien' => 'invalide'];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/edls/{$edl->id}/finalize", $payload)->assertStatus(422);
        }
        $this->postJson("/api/edls/{$edl->id}/finalize", $payload)->assertStatus(429);

        RateLimiter::clear('finalize');
    }

    public function test_login_and_logout_are_journaled(): void
    {
        $socialiteUser = SocialiteUser::fake(['id' => 'ms-9', 'name' => 'Jean Dupont', 'email' => 'jean@example.com']);
        $socialiteUser->setRaw(['givenName' => 'Jean', 'surname' => 'Dupont']);
        Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

        $this->get('/auth/microsoft/callback');
        $user = User::where('email', 'jean@example.com')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', ['action' => 'user_login', 'entity_id' => $user->id, 'user_id' => $user->id]);

        $this->post('/logout');
        $this->assertDatabaseHas('activity_logs', ['action' => 'user_logout', 'entity_id' => $user->id]);
    }

    public function test_failed_login_is_journaled(): void
    {
        Socialite::shouldReceive('driver->user')->once()->andThrow(new \Exception('state invalide'));

        $this->get('/auth/microsoft/callback')->assertRedirectContains('/login?error=');

        $this->assertDatabaseHas('activity_logs', ['action' => 'login_failed', 'user_id' => null]);
    }
}
