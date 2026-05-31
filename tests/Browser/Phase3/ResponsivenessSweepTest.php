<?php

declare(strict_types=1);

namespace Tests\Browser\Phase3;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Phase 3 — full-frontend responsiveness sweep.
 *
 * Visits every routed page at 3 Tailwind breakpoints (375 / 768 / 1024) and
 * saves screenshots under docs/frontend-alignment/responsive/. Each page is
 * wrapped in try/catch so one failure does not block the rest of the sweep.
 *
 * Pages that need a URL parameter use the seeded workspace/projet/activite/tache
 * (all id = 1 thanks to DatabaseTruncation) and $superAdmin->id for user params.
 *
 * Run: php artisan dusk tests/Browser/Phase3/ResponsivenessSweepTest.php
 * Requires: APP_ENV=dusk.local php artisan serve + work-tracking-dusk DB.
 *
 * Rate limiting is bypassed in dusk.local env (RouteServiceProvider).
 */
class ResponsivenessSweepTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /** Pages reachable without auth. */
    protected array $publicPages = [
        ['name' => 'signin', 'path' => '/signin'],
        ['name' => 'signup', 'path' => '/signup'],
        ['name' => 'forgot-password', 'path' => '/forgot-password'],
        ['name' => 'reset-password', 'path' => '/reset-password'],
        ['name' => 'error-404', 'path' => '/error-404'],
        ['name' => 'unauthorized', 'path' => '/unauthorized'],
    ];

    /** Tailwind breakpoints to capture (375=mobile, 640=sm:, 768=md:, 1024=lg:). */
    protected array $breakpoints = [375, 640, 768, 1024];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /**
     * Authed-only page list. Built dynamically because some URLs need IDs
     * resolved from the seeded entities.
     *
     * @return array<int,array{name:string,path:string}>
     */
    protected function authedPagesFor(User $superAdmin, int $workspaceId, int $projetId, int $activiteId, int $tacheId): array
    {
        return [
            // Dashboard + home
            ['name' => 'root',                          'path' => '/'],
            ['name' => 'dashboard',                     'path' => '/dashboard'],

            // Admin (super_admin only)
            ['name' => 'admin-dashboard',               'path' => '/admin/dashboard'],
            ['name' => 'admin-workspaces',              'path' => '/admin/workspaces'],
            ['name' => 'admin-users',                   'path' => '/admin/users'],
            ['name' => 'admin-roles',                   'path' => '/admin/roles'],

            // Workspaces
            ['name' => 'workspaces-list',               'path' => '/workspaces'],
            ['name' => 'workspaces-create',             'path' => '/workspaces/create'],
            ['name' => 'workspace-show',                'path' => "/workspaces/{$workspaceId}"],
            ['name' => 'workspace-edit',                'path' => "/workspaces/{$workspaceId}/edit"],
            ['name' => 'workspace-settings',            'path' => "/workspaces/{$workspaceId}/settings"],
            ['name' => 'workspace-subscription',        'path' => "/workspaces/{$workspaceId}/subscription"],
            ['name' => 'workspace-documents',           'path' => "/workspaces/{$workspaceId}/documents"],
            ['name' => 'workspace-taches',              'path' => '/workspace/taches'],

            // Projets
            ['name' => 'projets-list-all',              'path' => '/projets/list/all'],
            ['name' => 'projets-mes-projets',           'path' => '/projets/mes-projets'],
            ['name' => 'projets-archives',              'path' => '/projets/archives'],
            ['name' => 'projets-create',                'path' => '/projets/create'],
            ['name' => 'projet-show',                   'path' => "/projets/{$projetId}"],
            ['name' => 'projet-edit',                   'path' => "/projets/{$projetId}/edit"],
            ['name' => 'projet-documents',              'path' => "/projets/{$projetId}/documents"],

            // Activités
            ['name' => 'activites-all',                 'path' => '/activites/all/activity'],
            ['name' => 'activites-mes-activites',       'path' => '/activites/mes-activites'],
            ['name' => 'activites-en-retard',           'path' => '/activites/en-retard'],
            ['name' => 'activite-show',                 'path' => "/activites/{$activiteId}"],
            ['name' => 'activite-documents',            'path' => "/activites/{$activiteId}/documents"],

            // Tâches
            ['name' => 'taches-mes-taches',             'path' => '/taches/mes-taches'],
            ['name' => 'taches-assignees',              'path' => '/taches/assignees'],
            ['name' => 'taches-responsable',            'path' => '/taches/responsable'],
            ['name' => 'taches-coordination',           'path' => '/taches/coordination'],
            ['name' => 'taches-en-retard',              'path' => '/taches/en-retard'],
            ['name' => 'taches-resultats-en-attente',   'path' => '/taches/resultats/en-attente'],
            ['name' => 'taches-waiting-colleagues',     'path' => '/taches/waiting-colleagues'],
            ['name' => 'tache-show',                    'path' => "/taches/{$tacheId}"],
            ['name' => 'tache-documents',               'path' => "/taches/{$tacheId}/documents"],

            // Documents
            ['name' => 'documents',                     'path' => '/documents'],

            // Évaluations
            ['name' => 'evaluations-dashboard',         'path' => '/evaluations/dashboard'],
            ['name' => 'evaluations-tableau-de-bord',   'path' => '/evaluations/tableau-de-bord'],
            ['name' => 'evaluations-fiches',            'path' => '/evaluations/fiches'],
            ['name' => 'evaluations-performance',       'path' => '/evaluations/performance'],
            ['name' => 'evaluations-rapport-hebdo',     'path' => '/evaluations/rapport-hebdomadaire'],
            ['name' => 'evaluations-agent-sheet',       'path' => "/evaluations/personnel/{$superAdmin->id}/historique"],

            // Validations
            ['name' => 'validations-en-attente',        'path' => '/validations/en-attente'],
            ['name' => 'validations-a-traiter',         'path' => '/validations/a-traiter'],
            ['name' => 'mes-validations',               'path' => '/mes-validations'],

            // Labels
            ['name' => 'labels',                        'path' => '/labels'],
            ['name' => 'labels-templates',              'path' => '/labels/templates'],

            // Teams
            ['name' => 'teams',                         'path' => '/teams'],

            // Users
            ['name' => 'users',                         'path' => '/users'],
            ['name' => 'users-invitations',             'path' => '/users/invitations'],

            // Notifications + profile + prefs
            ['name' => 'notifications',                 'path' => '/notifications'],
            ['name' => 'notification-preferences',      'path' => '/notification-preferences'],
            ['name' => 'profile',                       'path' => '/profile'],
        ];
    }

    public function test_full_frontend_responsiveness_sweep(): void
    {
        [$superAdmin, $workspace, $projet, $activite, $tache] = $this->buildSeed();
        $outDir = base_path('docs/frontend-alignment/responsive');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0o755, true);
        }

        $authedPages = $this->authedPagesFor(
            $superAdmin,
            $workspace->id,
            $projet->id,
            $activite->id,
            $tache->id,
        );

        $report = [];

        $this->browse(function (Browser $browser) use ($superAdmin, $authedPages, $outDir, &$report) {
            // 1. Public pages — no auth.
            foreach ($this->publicPages as $page) {
                $this->capturePage($browser, $page, $outDir, $report);
            }

            // 2. Sign in with explicit super_admin payload (bypasses signInAs's hasRole quirk).
            $token = $superAdmin->createToken('phase3')->plainTextToken;
            $payload = json_encode([
                'id' => $superAdmin->id,
                'nom' => $superAdmin->nom,
                'email' => $superAdmin->email,
                'current_workspace_id' => $superAdmin->current_workspace_id,
                'is_super_admin' => true,
                'roles' => ['super_admin'],
            ], JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);

            $browser->visit('/signin')->waitFor('[dusk="email"]', 20);
            $browser->script([
                "localStorage.setItem('auth_token', '{$token}');",
                "localStorage.setItem('user', '{$payload}');",
            ]);
            $browser->visit('/admin/dashboard')->pause(1500);

            foreach ($authedPages as $page) {
                $this->capturePage($browser, $page, $outDir, $report);
            }
        });

        $lines = ['Phase 3 sweep report ('.count($report).' pages):'];
        foreach ($report as $entry) {
            $lines[] = \sprintf('  %-36s %s', $entry['name'], $entry['status']);
        }
        fwrite(STDERR, "\n".implode("\n", $lines)."\n");

        $captured = array_filter($report, fn ($e) => str_contains($e['status'], 'OK'));
        $this->assertNotEmpty($captured, 'Aucune capture d\'écran réussie.');
    }

    /**
     * Capture one page at all configured breakpoints. Failures are swallowed
     * and recorded so the sweep continues across pages.
     */
    protected function capturePage(Browser $browser, array $page, string $outDir, array &$report): void
    {
        try {
            $browser->visit($page['path']);
            // Petite pause après navigation pour laisser les composants Vue se monter.
            $browser->pause(700);

            $captured = [];
            foreach ($this->breakpoints as $w) {
                $browser->resize($w, 900);
                $browser->pause(250);
                $file = \sprintf('%s/%s-%d.png', $outDir, $page['name'], $w);
                $browser->driver->takeScreenshot($file);
                $captured[] = $w;
            }
            $report[] = ['name' => $page['name'], 'status' => 'OK ('.implode(' / ', $captured).')'];
        } catch (\Throwable $e) {
            $report[] = ['name' => $page['name'], 'status' => 'FAIL: '.substr($e->getMessage(), 0, 60)];
        }
    }

    /**
     * Construit le jeu de données minimal pour rendre les pages avec du contenu.
     *
     * @return array{0:User,1:Workspace,2:Projet,3:Activite,4:Tache}
     */
    protected function buildSeed(): array
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'email' => 'phase3-admin@worktracking.local',
        ]);
        $superAdmin->assignRole('super_admin');

        $workspace = Workspace::factory()->create([
            'owner_id' => $superAdmin->id,
            'is_active' => true,
        ]);
        $superAdmin->update(['current_workspace_id' => $workspace->id]);

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $superAdmin->id,
        ]);

        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $superAdmin->id,
        ]);

        $tache = null;
        foreach (range(1, 3) as $i) {
            $t = Tache::factory()->create([
                'activite_id' => $activite->id,
                'titre' => "Tâche démo Phase 3 #{$i}",
            ]);
            $tache ??= $t;
        }

        return [$superAdmin->fresh(), $workspace, $projet, $activite, $tache];
    }
}
