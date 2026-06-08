<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 8 — CRUD des plans (super_admin), avec garde-fous de suppression.
 *
 * Couvre :
 *  - super_admin liste / crée / modifie / supprime un plan
 *  - non-super-admin → 403 sur chaque action
 *  - validation (champs requis)
 *  - suppression refusée : plan gratuit, ou plan utilisé par un workspace
 */
class AdminPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function plainUser(): User
    {
        return User::factory()->create();
    }

    public function test_super_admin_lists_all_plans(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $this->getJson('/api/admin/plans')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['plans' => [['id', 'slug', 'nom_fr', 'workspaces_count']]]);
    }

    public function test_non_super_admin_cannot_list_plans(): void
    {
        Sanctum::actingAs($this->plainUser());

        $this->getJson('/api/admin/plans')->assertForbidden();
    }

    public function test_super_admin_creates_plan(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/admin/plans', [
            'slug' => 'enterprise',
            'nom_fr' => 'Entreprise',
            'nom_en' => 'Enterprise',
            'price' => 200000,
            'max_members' => -1,
            'max_storage_mb' => -1,
            'max_file_size_mb' => 250,
        ])->assertCreated()->assertJsonPath('plan.slug', 'enterprise');

        $this->assertDatabaseHas('plans', ['slug' => 'enterprise', 'currency' => 'XAF']);
    }

    public function test_create_validates_required_fields(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/admin/plans', [])->assertUnprocessable();
    }

    public function test_non_super_admin_cannot_create_plan(): void
    {
        Sanctum::actingAs($this->plainUser());

        $this->postJson('/api/admin/plans', ['slug' => 'x'])->assertForbidden();
    }

    public function test_super_admin_updates_plan(): void
    {
        $plan = Plan::where('slug', 'starter')->first();
        Sanctum::actingAs($this->superAdmin());

        $this->putJson("/api/admin/plans/{$plan->id}", ['price' => 19999])
            ->assertOk()
            ->assertJsonPath('plan.price', 19999);
    }

    public function test_super_admin_deletes_unused_paid_plan(): void
    {
        $plan = Plan::factory()->create(['is_free' => false]);
        Sanctum::actingAs($this->superAdmin());

        $this->deleteJson("/api/admin/plans/{$plan->id}")->assertOk();
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_cannot_delete_free_plan(): void
    {
        $free = Plan::where('slug', 'free')->first();
        Sanctum::actingAs($this->superAdmin());

        $this->deleteJson("/api/admin/plans/{$free->id}")->assertStatus(422);
        $this->assertDatabaseHas('plans', ['id' => $free->id]);
    }

    public function test_cannot_delete_plan_in_use(): void
    {
        $plan = Plan::factory()->create(['is_free' => false]);
        $owner = User::factory()->create();
        Workspace::factory()->create(['owner_id' => $owner->id, 'plan_id' => $plan->id]);

        Sanctum::actingAs($this->superAdmin());

        $this->deleteJson("/api/admin/plans/{$plan->id}")->assertStatus(422);
        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }
}
