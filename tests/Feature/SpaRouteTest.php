<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_spa_shell(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertOk();
    }

    public function test_authenticated_user_can_access_any_spa_subpath(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/edl/42')->assertOk();
    }
}
