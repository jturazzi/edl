<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function authUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/categories')->assertStatus(401);
    }

    public function test_index_returns_categories_ordered_by_name(): void
    {
        $this->authUser();
        Category::factory()->create(['name' => 'Zebra']);
        Category::factory()->create(['name' => 'Alpha']);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $names = collect($response->json())->pluck('name')->all();
        $this->assertSame(['Alpha', 'Zebra'], $names);
    }

    public function test_store_creates_category_with_default_color(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/categories', ['name' => 'Cuisine']);

        $response->assertCreated();
        $response->assertJsonPath('color', '#6366f1');
        $this->assertDatabaseHas('categories', ['name' => 'Cuisine', 'color' => '#6366f1']);
    }

    public function test_store_trims_name(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/categories', ['name' => '  Salon  ']);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', ['name' => 'Salon']);
    }

    public function test_store_accepts_custom_color(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/categories', ['name' => 'Salon', 'color' => '#ABCDEF']);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', ['name' => 'Salon', 'color' => '#ABCDEF']);
    }

    public function test_store_rejects_invalid_color_format(): void
    {
        $this->authUser();

        $response = $this->postJson('/api/categories', ['name' => 'Salon', 'color' => 'not-a-color']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['color']);
    }

    public function test_store_rejects_duplicate_name(): void
    {
        $this->authUser();
        Category::factory()->create(['name' => 'Cuisine']);

        $response = $this->postJson('/api/categories', ['name' => 'Cuisine']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_store_logs_activity(): void
    {
        $this->authUser();

        $this->postJson('/api/categories', ['name' => 'Cuisine'])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'category_created',
            'entity_type' => 'category',
        ]);
    }

    public function test_destroy_deletes_category(): void
    {
        $this->authUser();
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertOk();
        $response->assertJson(['deleted' => true]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_nullifies_related_edls_category(): void
    {
        $this->authUser();
        $category = Category::factory()->create();
        $edl = \App\Models\Edl::factory()->create(['category_id' => $category->id]);

        $this->deleteJson("/api/categories/{$category->id}")->assertOk();

        $this->assertNull($edl->fresh()->category_id);
    }

    public function test_destroy_logs_activity(): void
    {
        $this->authUser();
        $category = Category::factory()->create();

        $this->deleteJson("/api/categories/{$category->id}")->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'category_deleted',
            'entity_type' => 'category',
        ]);
    }
}
