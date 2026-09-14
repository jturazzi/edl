<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed_to_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_login_page_redirects_authenticated_users_home(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/login')->assertRedirect(route('home'));
    }

    public function test_redirect_to_microsoft_uses_socialite(): void
    {
        Socialite::shouldReceive('driver->redirect')
            ->once()
            ->andReturn(redirect('https://login.microsoftonline.com/fake'));

        $response = $this->get('/auth/microsoft');

        $response->assertRedirect('https://login.microsoftonline.com/fake');
    }

    public function test_callback_creates_new_user_and_logs_in(): void
    {
        $socialiteUser = SocialiteUser::fake([
            'id'    => 'ms-123',
            'name'  => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
        ]);
        $socialiteUser->setRaw(array_merge($socialiteUser->user, [
            'givenName' => 'Jean',
            'surname'   => 'Dupont',
        ]));

        Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

        $response = $this->get('/auth/microsoft/callback');

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'microsoft_id' => 'ms-123',
            'email'        => 'jean.dupont@example.com',
            'firstname'    => 'Jean',
            'lastname'     => 'Dupont',
        ]);
    }

    public function test_callback_updates_existing_user_matched_by_microsoft_id(): void
    {
        $existing = User::factory()->create([
            'microsoft_id' => 'ms-123',
            'email'        => 'old@example.com',
        ]);

        $socialiteUser = SocialiteUser::fake([
            'id'    => 'ms-123',
            'name'  => 'Jean Dupont',
            'email' => 'new@example.com',
        ]);
        $socialiteUser->setRaw(array_merge($socialiteUser->user, [
            'givenName' => 'Jean',
            'surname'   => 'Dupont',
        ]));

        Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

        $this->get('/auth/microsoft/callback');

        $this->assertSame(1, User::count());
        $this->assertSame('new@example.com', $existing->fresh()->email);
    }

    public function test_callback_redirects_to_login_with_error_on_exception(): void
    {
        Socialite::shouldReceive('driver->user')->once()->andThrow(new \Exception('invalid state'));

        $response = $this->get('/auth/microsoft/callback');

        $response->assertRedirect();
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
        $this->assertGuest();
    }

    public function test_logout_invalidates_session_and_redirects_to_login(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_returns_json_when_json_requested(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson('/logout');

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertGuest();
    }
}
