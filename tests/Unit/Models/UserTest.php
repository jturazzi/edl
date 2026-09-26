<?php

namespace Tests\Unit\Models;

use App\Models\Edl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_combines_firstname_and_lastname(): void
    {
        $user = User::factory()->make(['firstname' => 'Jean', 'lastname' => 'Dupont', 'name' => 'jdupont']);

        $this->assertSame('Jean Dupont', $user->full_name);
    }

    public function test_full_name_falls_back_to_name_when_no_firstname_or_lastname(): void
    {
        $user = User::factory()->make(['firstname' => null, 'lastname' => null, 'name' => 'Jean Dupont']);

        $this->assertSame('Jean Dupont', $user->full_name);
    }

    public function test_remember_token_is_hidden_from_serialization(): void
    {
        $user = User::factory()->create(['remember_token' => 'secret-token']);

        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function test_has_many_edls(): void
    {
        $user = User::factory()->create();
        Edl::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->fresh()->edls);
    }
}
