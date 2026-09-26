<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/logs')->assertStatus(401);
    }

    public function test_index_returns_logs_with_user_info(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Jean Dupont']);
        $this->actingAs($user);

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'edl_completed',
            'entity_type' => 'edl',
            'entity_id'   => 1,
            'details'     => ['foo' => 'bar'],
        ]);

        $response = $this->getJson('/api/logs');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.action', 'edl_completed');
        $response->assertJsonPath('0.user.name', 'Jean Dupont');
    }

    public function test_index_returns_null_user_when_log_has_no_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        ActivityLog::create([
            'user_id'     => null,
            'action'      => 'edl_deleted',
            'entity_type' => 'edl',
            'entity_id'   => 1,
        ]);

        $response = $this->getJson('/api/logs');

        $response->assertOk();
        $response->assertJsonPath('0.user', null);
    }

    public function test_index_orders_logs_by_latest_first(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $older = ActivityLog::create([
            'action' => 'edl_deleted', 'entity_type' => 'edl', 'entity_id' => 1,
        ]);
        $older->created_at = now()->subDay();
        $older->save();

        $newer = ActivityLog::create([
            'action' => 'edl_completed', 'entity_type' => 'edl', 'entity_id' => 2,
        ]);

        $response = $this->getJson('/api/logs');

        $response->assertOk();
        $response->assertJsonPath('0.id', $newer->id);
        $response->assertJsonPath('1.id', $older->id);
    }

    public function test_index_limits_results_to_200(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        ActivityLog::factory()->count(5)->create();

        $response = $this->getJson('/api/logs');

        $response->assertOk();
        $response->assertJsonCount(5);
    }
}
