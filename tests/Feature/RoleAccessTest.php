<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Edl;
use App\Models\EdlPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_are_techniciens_by_default(): void
    {
        $user = User::factory()->create()->fresh();

        $this->assertSame('technicien', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_endpoint_exposes_admin_flag(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->getJson('/api/user')->assertOk()->assertJsonPath('is_admin', true);

        $this->actingAs(User::factory()->create());
        $this->getJson('/api/user')->assertOk()->assertJsonPath('is_admin', false);
    }

    public function test_technicien_lists_every_edl(): void
    {
        $tech = User::factory()->create();
        Edl::factory()->create(['user_id' => $tech->id]);
        Edl::factory()->count(2)->create();

        $this->actingAs($tech);

        $this->getJson('/api/edls')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_admin_lists_every_edl(): void
    {
        Edl::factory()->count(3)->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson('/api/edls')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_technicien_can_read_but_not_change_someone_elses_edl(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $other = Edl::factory()->create();
        $photo = EdlPhoto::factory()->create(['edl_id' => $other->id]);
        Storage::disk('local')->put($photo->photo_path, 'img');

        // Consultation : autorisée
        $this->getJson("/api/edls/{$other->id}")->assertOk();
        $this->getJson("/api/edls/{$other->id}/photos")->assertOk();
        $this->get("/edl/photos/{$photo->id}")->assertOk();
        $this->getJson("/api/logement/edls?adresse=" . urlencode($other->adresse) . "&ville=" . urlencode((string) $other->ville))->assertOk();

        // Modification : réservée à l'auteur
        $this->postJson("/api/edls/{$other->id}/survey", ['survey_data' => '{}'])->assertForbidden();
        $this->patchJson("/api/edls/{$other->id}/steps", ['steps' => ['entree']])->assertForbidden();
        $this->postJson("/api/edls/{$other->id}/finalize", ['signature_technicien' => 'x', 'signature' => 'y'])->assertForbidden();
        $this->patchJson("/api/photos/{$photo->id}", ['caption' => 'x'])->assertForbidden();
        $this->deleteJson("/api/photos/{$photo->id}")->assertForbidden();

        $this->assertDatabaseHas('edls', ['id' => $other->id]);
    }

    public function test_show_and_comparison_tell_whether_the_edl_is_editable(): void
    {
        $tech = User::factory()->create();
        $entrant = Edl::factory()->create(['type' => 'entrant']);
        $others = Edl::factory()->create(['type' => 'sortant', 'entrant_id' => $entrant->id]);
        $mine = Edl::factory()->create(['type' => 'sortant', 'entrant_id' => $entrant->id, 'user_id' => $tech->id]);
        $this->actingAs($tech);

        $this->getJson("/api/edls/{$mine->id}")->assertJsonPath('can_edit', true);
        $this->getJson("/api/edls/{$others->id}")->assertJsonPath('can_edit', false);
        $this->getJson("/api/edls/{$mine->id}/comparison")->assertJsonPath('editable', true);
        $this->getJson("/api/edls/{$others->id}/comparison")->assertJsonPath('editable', false);
        $this->putJson("/api/edls/{$others->id}/retenues", ['retenues' => []])->assertForbidden();
    }

    public function test_technicien_cannot_delete_nor_archive_any_edl_even_his_own(): void
    {
        Storage::fake('local');
        $tech = User::factory()->create();
        $mine = Edl::factory()->create(['user_id' => $tech->id]);
        $other = Edl::factory()->create();
        $this->actingAs($tech);

        foreach ([$mine, $other] as $edl) {
            $this->deleteJson("/api/edls/{$edl->id}")->assertForbidden();
            $this->postJson("/api/edls/{$edl->id}/archive")->assertForbidden();
            $this->deleteJson("/api/edls/{$edl->id}/archive")->assertForbidden();
            $this->assertDatabaseHas('edls', ['id' => $edl->id, 'archived_at' => null]);
        }
    }

    public function test_technicien_can_work_on_his_own_edl(): void
    {
        $tech = User::factory()->create();
        $edl = Edl::factory()->create(['user_id' => $tech->id]);
        $this->actingAs($tech);

        $this->getJson("/api/edls/{$edl->id}")->assertOk();
        $this->postJson("/api/edls/{$edl->id}/survey", ['survey_data' => '{"a":"b"}'])->assertOk();
    }

    public function test_admin_can_open_any_edl(): void
    {
        $edl = Edl::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson("/api/edls/{$edl->id}")->assertOk();
    }

    public function test_admin_endpoints_are_forbidden_to_techniciens(): void
    {
        $this->actingAs($tech = User::factory()->create());

        $this->getJson('/api/admin/info')->assertForbidden();
        $this->getJson('/api/logs')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->patchJson("/api/admin/users/{$tech->id}", ['role' => 'admin'])->assertForbidden();
        $this->assertSame('technicien', $tech->fresh()->role);
    }

    public function test_admin_lists_users_with_role_and_edl_count(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $tech = User::factory()->create(['name' => 'Zoe', 'firstname' => 'Zoe', 'lastname' => 'Test']);
        Edl::factory()->count(2)->create(['user_id' => $tech->id]);

        $row = collect($this->getJson('/api/admin/users')->assertOk()->json())->firstWhere('id', $tech->id);

        $this->assertSame('technicien', $row['role']);
        $this->assertSame(2, $row['edls_count']);
    }

    public function test_admin_changes_a_role_and_it_is_logged(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $tech = User::factory()->create();

        $this->patchJson("/api/admin/users/{$tech->id}", ['role' => 'admin'])->assertOk()->assertJsonPath('role', 'admin');

        $this->assertTrue($tech->fresh()->isAdmin());
        $this->assertDatabaseHas('activity_logs', ['action' => 'user_role_changed', 'entity_type' => 'user', 'entity_id' => $tech->id]);
    }

    public function test_role_must_be_valid(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $tech = User::factory()->create();

        $this->patchJson("/api/admin/users/{$tech->id}", ['role' => 'root'])->assertStatus(422);
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}", ['role' => 'technicien'])->assertStatus(422);
        $this->assertTrue($admin->fresh()->isAdmin());

        $second = User::factory()->admin()->create();
        $this->patchJson("/api/admin/users/{$second->id}", ['role' => 'technicien'])->assertOk();
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $user->update(['role' => 'admin']);

        $this->assertSame('technicien', $user->fresh()->role);
    }

    public function test_set_role_command(): void
    {
        $user = User::factory()->create(['email' => 'promo@example.test']);

        $this->artisan('user:role', ['email' => 'promo@example.test', 'role' => 'admin'])->assertSuccessful();
        $this->assertTrue($user->fresh()->isAdmin());

        $this->artisan('user:role', ['email' => 'promo@example.test', 'role' => 'root'])->assertFailed();
        $this->artisan('user:role', ['email' => 'nobody@example.test', 'role' => 'admin'])->assertFailed();
    }
}
