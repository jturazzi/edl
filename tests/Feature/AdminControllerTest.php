<?php

namespace Tests\Feature;

use App\Models\Edl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_info_requires_authentication(): void
    {
        $this->getJson('/api/admin/info')->assertStatus(401);
    }

    public function test_info_returns_correct_counts(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Edl::factory()->count(2)->create(['type' => 'entrant', 'status' => 'en_cours']);
        Edl::factory()->count(3)->create(['type' => 'sortant', 'status' => 'complete']);

        $response = $this->getJson('/api/admin/info');

        $response->assertOk();
        $response->assertJson([
            'stats' => [
                'edl_total'    => 5,
                'edl_entrant'  => 2,
                'edl_sortant'  => 3,
                'edl_en_cours' => 2,
                'edl_complete' => 3,
            ],
        ]);
    }

    public function test_info_returns_zeros_when_no_edls(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->getJson('/api/admin/info');

        $response->assertOk();
        $response->assertJson([
            'stats' => [
                'edl_total'    => 0,
                'edl_entrant'  => 0,
                'edl_sortant'  => 0,
                'edl_en_cours' => 0,
                'edl_complete' => 0,
            ],
        ]);
    }
}
