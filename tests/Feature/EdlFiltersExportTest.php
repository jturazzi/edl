<?php

namespace Tests\Feature;

use App\Models\Edl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdlFiltersExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    private function ids(string $query): array
    {
        return collect($this->getJson('/api/edls?' . $query)->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
    }

    public function test_filters_by_type_and_status(): void
    {
        $a = Edl::factory()->create(['type' => 'entrant', 'status' => 'en_cours']);
        $b = Edl::factory()->create(['type' => 'entrant', 'status' => 'complete']);
        $c = Edl::factory()->create(['type' => 'sortant', 'status' => 'complete']);

        $this->assertSame([$a->id, $b->id], $this->ids('type=entrant'));
        $this->assertSame([$b->id, $c->id], $this->ids('status=complete'));
        $this->assertSame([$b->id], $this->ids('type=entrant&status=complete'));
        $this->assertSame([$a->id, $b->id, $c->id], $this->ids('type=nimporte&status=quoi'), 'Une valeur inconnue est ignorée.');
    }

    public function test_filters_by_technicien_email(): void
    {
        $a = Edl::factory()->create(['technicien_email' => 'a@example.test']);
        Edl::factory()->create(['technicien_email' => 'b@example.test']);

        $this->assertSame([$a->id], $this->ids('technicien=a@example.test'));
    }

    public function test_filters_by_period_with_inclusive_bounds(): void
    {
        $early = Edl::factory()->create(['date_edl' => '2026-01-10 08:00:00']);
        $mid = Edl::factory()->create(['date_edl' => '2026-02-15 23:30:00']);
        $late = Edl::factory()->create(['date_edl' => '2026-03-20 09:00:00']);

        $this->assertSame([$mid->id, $late->id], $this->ids('from=2026-02-15'));
        $this->assertSame([$early->id, $mid->id], $this->ids('to=2026-02-15'));
        $this->assertSame([$mid->id], $this->ids('from=2026-02-01&to=2026-02-28'));
        $this->assertSame([$early->id, $mid->id, $late->id], $this->ids('from=pas-une-date'));
    }

    public function test_sorting_and_default_order(): void
    {
        $b = Edl::factory()->create(['adresse' => 'B rue']);
        $a = Edl::factory()->create(['adresse' => 'A rue']);

        $asc = collect($this->getJson('/api/edls?sort=adresse&dir=asc')->json('data'))->pluck('id')->all();
        $desc = collect($this->getJson('/api/edls?sort=adresse&dir=desc')->json('data'))->pluck('id')->all();
        $default = collect($this->getJson('/api/edls')->json('data'))->pluck('id')->all();

        $this->assertSame([$a->id, $b->id], $asc);
        $this->assertSame([$b->id, $a->id], $desc);
        $this->assertSame([$a->id, $b->id], $default, 'Par défaut : le plus récent en premier.');
        $this->getJson('/api/edls?sort=password')->assertOk();
    }

    public function test_filters_endpoint_lists_distinct_techniciens(): void
    {
        Edl::factory()->create(['technicien_prenom' => 'Zoe', 'technicien_nom' => 'Alpha', 'technicien_email' => 'zoe@example.test']);
        Edl::factory()->create(['technicien_prenom' => 'Zoe', 'technicien_nom' => 'Alpha', 'technicien_email' => 'zoe@example.test']);
        Edl::factory()->create(['technicien_prenom' => 'Al', 'technicien_nom' => 'Beta', 'technicien_email' => 'al@example.test']);

        $res = $this->getJson('/api/edls/filters')->assertOk();

        $this->assertSame(['al@example.test', 'zoe@example.test'], collect($res->json('techniciens'))->pluck('email')->all());
        $this->assertSame('Al Beta', $res->json('techniciens.0.name'));

        // Un technicien voit la même liste : tous les EDL lui sont consultables
        $this->actingAs(User::factory()->create());
        $this->assertCount(2, $this->getJson('/api/edls/filters')->json('techniciens'));
    }

    public function test_export_returns_a_csv_with_bom_and_respects_filters(): void
    {
        Edl::factory()->create(['type' => 'entrant', 'status' => 'complete', 'adresse' => '1 rue Alpha', 'ville' => 'Nantes', 'locataire_prenom' => 'Éva', 'locataire_nom' => 'DURAND', 'technicien_prenom' => 'Jean', 'technicien_nom' => 'Dupont', 'technicien_email' => 'jean@example.test', 'date_edl' => '2026-03-15 10:30:00']);
        Edl::factory()->create(['type' => 'sortant', 'adresse' => '2 rue Beta']);

        $response = $this->get('/api/edls/export?type=entrant');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = array_values(array_filter(explode("\n", trim(substr($csv, 3)))));
        $this->assertCount(2, $lines, 'En-tête + 1 EDL entrant.');
        $this->assertStringContainsString('"N° EDL";Type;Statut', $lines[0]);
        $this->assertStringContainsString('Entrant;Terminé;"1 rue Alpha";Nantes;"Éva DURAND"', $lines[1]);
        $this->assertStringContainsString('15/03/2026 10:30', $lines[1]);
        $this->assertStringNotContainsString('2 rue Beta', $csv);
    }

    public function test_technicien_exports_every_edl(): void
    {
        $tech = User::factory()->create();
        Edl::factory()->create(['user_id' => $tech->id, 'adresse' => 'Ma rue']);
        Edl::factory()->create(['adresse' => 'Rue des autres']);
        $this->actingAs($tech);

        $csv = $this->get('/api/edls/export')->streamedContent();

        $this->assertStringContainsString('Ma rue', $csv);
        $this->assertStringContainsString('Rue des autres', $csv);
    }

    public function test_export_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();
        auth()->logout();

        $this->getJson('/api/edls/export')->assertStatus(401);
        $this->getJson('/api/edls/filters')->assertStatus(401);
    }
}
