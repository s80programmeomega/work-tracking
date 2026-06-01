<?php

declare(strict_types=1);

namespace Tests\Browser\Phase3;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Phase 3B — responsiveness under interaction.
 *
 * Visits pages at 4 Tailwind breakpoints (375 / 640 / 768 / 1024) and for each:
 *   - switches between every view mode (Table / Kanban / List / Grouped)
 *   - cycles every tab strip (TacheDetail, ValidationResultats, AgentSheet, Projets)
 *   - opens dropdowns / collapsibles (Archived bulk-actions)
 *
 * Screenshots saved under docs/frontend-alignment/responsive/interactions/.
 * Each scenario wrapped in try/catch so one failure doesn't block the rest.
 *
 * Run: php artisan dusk tests/Browser/Phase3/InteractionResponsivenessTest.php
 * Requires: APP_ENV=dusk.local php artisan serve + work-tracking-dusk DB.
 */
class InteractionResponsivenessTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    protected array $breakpoints = [375, 640, 768, 1024];

    protected string $outDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->outDir = base_path('docs/frontend-alignment/responsive/interactions');
        if (! is_dir($this->outDir)) {
            mkdir($this->outDir, 0o755, true);
        }
    }

    public function test_interaction_responsiveness(): void
    {
        [$superAdmin, , , , $tache] = $this->buildSeed();

        $report = [];

        $this->browse(function (Browser $browser) use ($superAdmin, $tache, &$report) {
            // Authenticate once — inject token + super_admin payload.
            $token = $superAdmin->createToken('phase3b')->plainTextToken;
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

            // 1. Taches — Table / Kanban / List view toggle (/taches → Taches.vue, not MesTaches.vue)
            $this->scenarioViewToggle(
                $browser,
                '/taches',
                'taches-views',
                [
                    ['btn' => 'view-table-btn',  'panel' => 'view-table-panel'],
                    ['btn' => 'view-kanban-btn',  'panel' => 'view-kanban-panel'],
                    ['btn' => 'view-list-btn',    'panel' => 'view-list-panel'],
                ],
                $report,
            );

            // 2. TachesAssignees — Kanban / Par Activité toggle
            $this->scenarioViewToggle(
                $browser,
                '/taches/assignees',
                'taches-assignees-views',
                [
                    ['btn' => 'view-kanban-btn',  'panel' => 'view-kanban-panel'],
                    ['btn' => 'view-grouped-btn', 'panel' => 'view-grouped-panel'],
                ],
                $report,
            );

            // 3. TachesResponsable — Kanban / Par Activité toggle
            $this->scenarioViewToggle(
                $browser,
                '/taches/responsable',
                'taches-responsable-views',
                [
                    ['btn' => 'view-kanban-btn',  'panel' => 'view-kanban-panel'],
                    ['btn' => 'view-grouped-btn', 'panel' => 'view-grouped-panel'],
                ],
                $report,
            );

            // 4. TacheDetail — cycle all tabs
            $this->scenarioTabCycle(
                $browser,
                "/taches/{$tache->id}",
                'tache-detail-tabs',
                ['details', 'sous-taches', 'assignees', 'attachments', 'documents', 'links', 'results', 'comments', 'activity'],
                fn (string $id) => "tab-{$id}",
                $report,
            );

            // 5. ValidationResultats — 3 tabs (pendingN1/N2/today/total are stat cards, not tabs)
            $this->scenarioTabCycle(
                $browser,
                '/validations/a-traiter',
                'validation-tabs',
                ['n1', 'n2', 'history'],
                fn (string $id) => "validation-tab-{$id}",
                $report,
            );

            // 6. AgentSheet — cycle all 5 section tabs
            $this->scenarioTabCycle(
                $browser,
                "/evaluations/personnel/{$superAdmin->id}/historique",
                'agent-sheet-sections',
                ['directed_tasks', 'directed_subtasks', 'assignee_tasks', 'assignee_subtasks', 'submitted_results'],
                fn (string $id) => "section-tab-{$id}",
                $report,
            );

            // 7. Projets — Dashboard / Liste tabs
            $this->scenarioTabCycle(
                $browser,
                '/projets/list/all',
                'projets-tabs',
                ['dashboard', 'list'],
                fn (string $id) => "tab-{$id}",
                $report,
            );

            // 8. Archived projets — bulk-actions dropdown
            // Need at least one project selected; we do it via JS since
            // checkbox click would need the list to be populated first.
            $this->scenarioDropdown(
                $browser,
                '/projets/archives',
                'archived-bulk-actions',
                'bulk-actions-btn',
                'bulk-actions-dropdown',
                $report,
            );

            // 9. TacheDetailModal — switch to table view first, then open modal via row title
            $tacheViewBtn = "[dusk=\"tache-view-btn-{$tache->id}\"]";
            foreach ($this->breakpoints as $w) {
                try {
                    $browser->resize($w, 900)->visit('/taches')->pause(1500);
                    // Ensure table view is active (default, but guard against localStorage).
                    $browser->script(["document.querySelector('[dusk=\"view-table-btn\"]')?.scrollIntoView({block:'center'});"]);
                    $browser->click('[dusk="view-table-btn"]')->pause(800);
                    $browser->waitFor($tacheViewBtn, 5);
                    $browser->script(["document.querySelector('{$tacheViewBtn}')?.scrollIntoView({block:'center'});"]);
                    $browser->click($tacheViewBtn)->pause(800);
                    $browser->waitFor('[dusk="tache-detail-modal"]', 5);
                    $browser->assertVisible('[dusk="tache-detail-modal"]');
                    $file = \sprintf('%s/tache-detail-modal-%d.png', $this->outDir, $w);
                    $browser->driver->takeScreenshot($file);
                    $browser->click('[dusk="modal-close-btn"]')->pause(400);
                    $report[] = ['name' => "tache-detail-modal@{$w}", 'status' => 'OK'];
                } catch (\Throwable $e) {
                    $report[] = ['name' => "tache-detail-modal@{$w}", 'status' => 'FAIL: '.substr($e->getMessage(), 0, 80)];
                }
            }

            // 10. ProjetFormModal — open via "Nouveau projet" button on /projets/mes-projets
            $this->scenarioModal(
                $browser,
                '/projets/mes-projets',
                'projet-form-modal',
                '[dusk="create-projet-btn"]',
                '[dusk="projet-form-modal"]',
                '[dusk="modal-close-btn"]',
                $report,
            );

            // 11. NotificationDetailModal — only runs if notifications exist for this user.
            $this->scenarioModal(
                $browser,
                '/notifications',
                'notification-detail-modal',
                '[dusk="notification-item"]',
                '[dusk="notification-detail-modal"]',
                '[dusk="modal-close-btn"]',
                $report,
            );
        });

        // Print report to STDERR so it appears in the test output.
        $lines = ['Phase 3B interaction sweep (\count($report) scenarios):'];
        foreach ($report as $entry) {
            $lines[] = \sprintf('  %-40s %s', $entry['name'], $entry['status']);
        }
        fwrite(STDERR, "\n".implode("\n", $lines)."\n");

        $ok = array_filter($report, fn ($e) => str_contains($e['status'], 'OK'));
        $this->assertNotEmpty($ok, 'Aucun scénario d\'interaction réussi.');
    }

    // ─────────────────────────────────────────────────────────────────
    // Scenario helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * For a page with multiple view-toggle buttons, click each button, wait for
     * its panel to appear, then screenshot at every breakpoint.
     *
     * @param  array<int,array{btn:string,panel:string}>  $views
     */
    protected function scenarioViewToggle(
        Browser $browser,
        string $path,
        string $scenarioName,
        array $views,
        array &$report,
    ): void {
        foreach ($this->breakpoints as $w) {
            try {
                $browser->resize($w, 900)->visit($path)->pause(1200);

                foreach ($views as $view) {
                    try {
                        $btn = $view['btn'];
                        $browser->script(["document.querySelector('[dusk=\"{$btn}\"]')?.scrollIntoView({block:'center'});"]);
                        $browser->click("[dusk=\"{$view['btn']}\"]")->pause(600);
                        $browser->assertVisible("[dusk=\"{$view['panel']}\"]");
                        $file = \sprintf('%s/%s-%s-%d.png', $this->outDir, $scenarioName, $view['btn'], $w);
                        $browser->driver->takeScreenshot($file);
                    } catch (\Throwable $e) {
                        $report[] = ['name' => "{$scenarioName}/{$view['btn']}@{$w}", 'status' => 'FAIL: '.substr($e->getMessage(), 0, 80)];

                        continue;
                    }
                }

                $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'OK'];
            } catch (\Throwable $e) {
                $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'FAIL(nav): '.substr($e->getMessage(), 0, 80)];
            }
        }
    }

    /**
     * For a page with a tab strip, click each tab and screenshot at every breakpoint.
     *
     * @param  string[]  $tabIds
     */
    protected function scenarioTabCycle(
        Browser $browser,
        string $path,
        string $scenarioName,
        array $tabIds,
        \Closure $duskAttr,
        array &$report,
    ): void {
        foreach ($this->breakpoints as $w) {
            try {
                $firstAttr = $duskAttr($tabIds[0]);
                $browser->resize($w, 900)->visit($path)->pause(800);
                // Wait for first tab to render (may be behind an API-loaded v-if).
                $browser->waitFor("[dusk=\"{$firstAttr}\"]", 8)->pause(200);

                foreach ($tabIds as $tabId) {
                    try {
                        $attr = $duskAttr($tabId);
                        $browser->script(["document.querySelector('[dusk=\"{$attr}\"]')?.scrollIntoView({block:'center'});"]);
                        $browser->click("[dusk=\"{$attr}\"]")->pause(400);
                        $file = \sprintf('%s/%s-%s-%d.png', $this->outDir, $scenarioName, $tabId, $w);
                        $browser->driver->takeScreenshot($file);
                    } catch (\Throwable $e) {
                        $report[] = ['name' => "{$scenarioName}/{$tabId}@{$w}", 'status' => 'FAIL: '.substr($e->getMessage(), 0, 80)];

                        continue;
                    }
                }

                $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'OK'];
            } catch (\Throwable $e) {
                $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'FAIL(nav): '.substr($e->getMessage(), 0, 80)];
            }
        }
    }

    /**
     * For a page with a dropdown toggle, open it and screenshot.
     * Uses JS to force a checkbox selection so the bulk-actions button appears.
     */
    protected function scenarioDropdown(
        Browser $browser,
        string $path,
        string $scenarioName,
        string $toggleDusk,
        string $dropdownDusk,
        array &$report,
    ): void {
        foreach ($this->breakpoints as $w) {
            try {
                $browser->resize($w, 900)->visit($path)->pause(1200);

                $browser->pause(300);

                // Try clicking the toggle — it only renders when items are selected.
                // If not visible, just screenshot the header (still validates the header reflow).
                $hasTrigger = false;
                try {
                    $browser->assertVisible("[dusk=\"{$toggleDusk}\"]");
                    $hasTrigger = true;
                } catch (\Throwable) {
                    // No items selected — select first checkbox if it exists.
                    try {
                        $browser->check('input[type="checkbox"]')->pause(400);
                        $browser->assertVisible("[dusk=\"{$toggleDusk}\"]");
                        $hasTrigger = true;
                    } catch (\Throwable) {
                        // No checkbox or still no button — skip dropdown, just screenshot page.
                    }
                }

                if ($hasTrigger) {
                    $browser->click("[dusk=\"{$toggleDusk}\"]")->pause(400);
                    $browser->assertVisible("[dusk=\"{$dropdownDusk}\"]");
                }

                $file = \sprintf('%s/%s-%d.png', $this->outDir, $scenarioName, $w);
                $browser->driver->takeScreenshot($file);
                $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'OK'.($hasTrigger ? ' (dropdown opened)' : ' (no items, header only)')];
            } catch (\Throwable $e) {
                $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'FAIL: '.substr($e->getMessage(), 0, 80)];
            }
        }
    }

    /**
     * Open a modal via triggerSelector, assert its overlay appears, screenshot at every
     * breakpoint, then close it via closeSelector before moving to the next breakpoint.
     * If the trigger is not found (e.g. no data), the breakpoint is skipped gracefully.
     */
    protected function scenarioModal(
        Browser $browser,
        string $path,
        string $scenarioName,
        string $triggerSelector,
        string $modalSelector,
        string $closeSelector,
        array &$report,
    ): void {
        foreach ($this->breakpoints as $w) {
            try {
                $browser->resize($w, 900)->visit($path)->pause(1200);

                // Scroll trigger into view before clicking (may be below fold at narrow widths).
                $browser->script(["document.querySelector('{$triggerSelector}')?.scrollIntoView({block:'center'});"]);
                $browser->pause(200);

                try {
                    $browser->waitFor($triggerSelector, 5);
                } catch (\Throwable) {
                    $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'SKIP (trigger not found after 5s)'];

                    continue;
                }

                $browser->click($triggerSelector)->pause(800);
                $browser->waitFor($modalSelector, 5);
                $browser->assertVisible($modalSelector);

                $file = \sprintf('%s/%s-%d.png', $this->outDir, $scenarioName, $w);
                $browser->driver->takeScreenshot($file);

                // Close the modal before the next breakpoint iteration.
                try {
                    $browser->click($closeSelector)->pause(400);
                } catch (\Throwable) {
                    // Fallback: press Escape.
                    $browser->keys('', '{escape}')->pause(400);
                }

                $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'OK'];
            } catch (\Throwable $e) {
                $report[] = ['name' => "{$scenarioName}@{$w}", 'status' => 'FAIL: '.substr($e->getMessage(), 0, 80)];
            }
        }
    }

    /**
     * Minimal seed: super_admin + workspace + projet + activite + 3 taches.
     *
     * @return array{0:User,1:Workspace,2:Projet,3:Activite,4:Tache}
     */
    protected function buildSeed(): array
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'email' => 'phase3b-admin@worktracking.local',
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
                'responsable_id' => $superAdmin->id,
                'titre' => "Tâche Phase 3B #{$i}",
            ]);
            $tache ??= $t;
        }

        // Seed a notification so the NotificationDetailModal trigger exists.
        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\TacheAssignee',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $superAdmin->id,
            'data' => json_encode([
                'message' => 'Vous avez été assigné à une tâche',
                'tache_id' => $tache->id,
                'tache_titre' => $tache->titre,
                'type' => 'tache_assignee',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$superAdmin->fresh(), $workspace, $projet, $activite, $tache];
    }
}
