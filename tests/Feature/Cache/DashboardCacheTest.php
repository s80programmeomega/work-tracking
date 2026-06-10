<?php

declare(strict_types=1);

namespace Tests\Feature\Cache;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cache du tableau de bord : réponse mise en cache par utilisateur, et CLÉ
 * cloisonnée — un utilisateur ne reçoit jamais le tableau de bord d'un autre.
 */
class DashboardCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        Cache::flush();
    }

    private function userWithWorkspace(): User
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $user;
    }

    public function test_dashboard_response_is_cached_per_user(): void
    {
        $user = $this->userWithWorkspace();
        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')->assertOk();

        // Une entrée de cache propre à cet utilisateur doit exister.
        $this->assertTrue(
            Cache::has('dashboard:'.$user->id.':accessible:m=-:s=-:p=-'),
            'Le tableau de bord devrait être mis en cache sous une clé propre à l\'utilisateur.'
        );
    }

    public function test_dashboard_cache_keys_are_isolated_between_users(): void
    {
        $a = $this->userWithWorkspace();
        $b = $this->userWithWorkspace();

        Sanctum::actingAs($a);
        $this->getJson('/api/dashboard')->assertOk();

        Sanctum::actingAs($b);
        $this->getJson('/api/dashboard')->assertOk();

        // Deux clés distinctes → aucune réutilisation inter-utilisateur.
        $this->assertTrue(Cache::has('dashboard:'.$a->id.':accessible:m=-:s=-:p=-'));
        $this->assertTrue(Cache::has('dashboard:'.$b->id.':accessible:m=-:s=-:p=-'));
        $this->assertNotSame(
            'dashboard:'.$a->id.':accessible:m=-:s=-:p=-',
            'dashboard:'.$b->id.':accessible:m=-:s=-:p=-'
        );
    }
}
