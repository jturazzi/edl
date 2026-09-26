<?php

namespace Tests\Feature;

use App\Models\Edl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_technicien_sees_every_edl_in_figures_but_resumes_only_his_own(): void
    {
        $tech = User::factory()->create();
        $mine = Edl::factory()->create(['user_id' => $tech->id, 'status' => 'en_cours']);
        Edl::factory()->create(['status' => 'en_cours']);
        $done = Edl::factory()->complete()->create();
        $this->actingAs($tech);

        $res = $this->getJson('/api/dashboard')->assertOk();

        $res->assertJsonPath('stats.total', 3)->assertJsonPath('stats.en_cours', 1);
        $this->assertSame([$mine->id], collect($res->json('en_cours'))->pluck('id')->all());
        $this->assertSame([$done->id], collect($res->json('recents'))->pluck('id')->all());
    }

    public function test_admin_sees_everything_with_monthly_figures(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Edl::factory()->create(['type' => 'entrant', 'status' => 'en_cours']);
        $done = Edl::factory()->complete()->create(['type' => 'sortant']);
        $old = Edl::factory()->complete()->create(['type' => 'entrant']);
        $old->forceFill(['created_at' => now()->subMonths(2), 'updated_at' => now()->subMonths(2)])->saveQuietly();

        $res = $this->getJson('/api/dashboard')->assertOk();

        $res->assertJsonPath('stats.total', 3)
            ->assertJsonPath('stats.en_cours', 1)
            ->assertJsonPath('stats.termines_mois', 1)
            ->assertJsonPath('stats.entrants_mois', 1)
            ->assertJsonPath('stats.sortants_mois', 1);
        $this->assertSame($done->id, $res->json('recents.0.id'));
        $this->assertCount(2, $res->json('recents'));
    }

    public function test_lists_are_capped_and_omit_heavy_columns(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Edl::factory()->count(12)->create(['status' => 'en_cours', 'survey_data' => ['a' => 'b']]);

        $res = $this->getJson('/api/dashboard')->assertOk();

        $this->assertCount(8, $res->json('en_cours'));
        $this->assertArrayNotHasKey('survey_data', $res->json('en_cours.0'));
        $this->assertArrayHasKey('numero', $res->json('en_cours.0'));
    }
}
