<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_404_when_no_token_configured(): void
    {
        config(['app.version_check_token' => null]);

        $this->getJson('/version-info')->assertStatus(404);
    }

    public function test_returns_403_when_token_is_missing(): void
    {
        config(['app.version_check_token' => 'secret-token']);

        $this->getJson('/version-info')->assertStatus(403);
    }

    public function test_returns_403_when_token_is_invalid(): void
    {
        config(['app.version_check_token' => 'secret-token']);

        $this->getJson('/version-info', ['X-Version-Token' => 'wrong-token'])->assertStatus(403);
    }

    public function test_returns_version_info_when_token_is_valid(): void
    {
        config(['app.version_check_token' => 'secret-token']);

        $response = $this->getJson('/version-info', ['X-Version-Token' => 'secret-token']);

        $response->assertOk();
        $response->assertJsonStructure([
            'app_name',
            'app_version',
            'laravel_version',
            'php_version',
            'environment',
            'checked_at',
        ]);
    }
}
