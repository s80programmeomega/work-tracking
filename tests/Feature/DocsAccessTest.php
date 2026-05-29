<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests d'accès aux routes de documentation API (Scribe + Swagger UI).
 *
 * Routes couvertes :
 *   GET /docs               — portail web Blade (web middleware, pas d'auth serveur)
 *   GET /api/docs           — UI Scribe          (auth:sanctum obligatoire)
 *   GET /api/docs/swagger   — Swagger UI         (auth:sanctum obligatoire)
 *   GET /api/docs.json      — spec OpenAPI JSON  (auth:sanctum obligatoire)
 *   GET /api/docs.openapi   — spec OpenAPI YAML  (auth:sanctum obligatoire)
 *   GET /api/docs.postman   — collection Postman (auth:sanctum obligatoire)
 */
class DocsAccessTest extends TestCase
{
    use RefreshDatabase;

    // ── Routes API protégées ────────────────────────────────────────────

    /** @return list<array{string}> */
    public static function protectedApiRoutes(): array
    {
        return [
            ['/api/docs'],
            ['/api/docs/swagger'],
            ['/api/docs.json'],
            ['/api/docs.openapi'],
            ['/api/docs.postman'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider protectedApiRoutes
     */
    public function unauthenticated_request_to_api_docs_returns_401(string $route): void
    {
        $this->getJson($route)->assertUnauthorized();
    }

    /**
     * @test
     *
     * @dataProvider protectedApiRoutes
     */
    public function authenticated_user_can_access_api_docs(string $route): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson($route)
            ->assertSuccessful();
    }

    // ── Portail web /docs ───────────────────────────────────────────────

    /** @test */
    public function web_docs_portal_is_publicly_accessible(): void
    {
        // La page /docs est une vue Blade pure — pas d'auth serveur.
        // L'authentification est gérée côté client via localStorage.
        $this->get('/docs')->assertOk();
    }

    /** @test */
    public function web_docs_portal_contains_expected_ui_elements(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee('Scribe', false)
            ->assertSee('Swagger UI', false)
            ->assertSee('localStorage', false)
            ->assertSee('auth_token', false);
    }

    /** @test */
    public function web_docs_portal_references_correct_api_urls(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee(route('scribe'), false)
            ->assertSee(route('scribe.docs.json'), false)
            ->assertSee(route('scribe.openapi'), false)
            ->assertSee(route('scribe.postman'), false);
    }

    // ── Contenu des endpoints protégés ─────────────────────────────────

    /** @test */
    public function openapi_json_endpoint_returns_valid_spec(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/docs.json')
            ->assertOk()
            ->assertJsonStructure(['openapi', 'info', 'paths'])
            ->assertJsonPath('openapi', '3.0.3');
    }

    /** @test */
    public function openapi_yaml_endpoint_returns_yaml_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/docs.openapi');

        $response->assertOk();
        // BinaryFileResponse — on lit le contenu via getFile()
        $content = $response->getFile()?->getContent() ?? $response->getContent();
        $this->assertStringContainsString('openapi:', $content);
    }

    /** @test */
    public function postman_endpoint_returns_valid_collection(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/docs.postman')
            ->assertOk()
            ->assertJsonStructure(['info', 'item']);
    }

    /** @test */
    public function scribe_ui_returns_html_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->get('/api/docs')
            ->assertOk()
            ->assertSee('<!doctype html>', false);
    }

    /** @test */
    public function swagger_ui_returns_html_with_swagger_assets(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->get('/api/docs/swagger')
            ->assertOk()
            ->assertSee('swagger-ui', false);
    }
}
