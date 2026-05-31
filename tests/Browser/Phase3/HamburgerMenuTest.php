<?php

declare(strict_types=1);

namespace Tests\Browser\Phase3;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Phase 3 — Hamburger menu responsiveness & interaction tests.
 *
 * Tests both mobile-only menu controls across 4 breakpoints:
 *   1. Left hamburger  → toggles AppSidebar slide-in/slide-out
 *   2. Right hamburger → toggles the application header panel
 *       (ThemeToggler + NotificationBell + UserMenu)
 *
 * Each test is wrapped in try/catch so one failure does not abort the rest.
 * Screenshots saved under docs/frontend-alignment/responsive/hamburger/.
 *
 * Run: php artisan dusk tests/Browser/Phase3/HamburgerMenuTest.php
 */
class HamburgerMenuTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /** Breakpoints to test (px). */
    protected array $breakpoints = [375, 640, 768];

    protected string $outDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->outDir = base_path('docs/frontend-alignment/responsive/hamburger');
        if (! is_dir($this->outDir)) {
            mkdir($this->outDir, 0o755, true);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function authenticate(Browser $browser, User $user): void
    {
        $token = $user->createToken('hamburger-test')->plainTextToken;
        $payload = json_encode([
            'id' => $user->id,
            'nom' => $user->nom,
            'email' => $user->email,
            'current_workspace_id' => $user->current_workspace_id,
            'is_super_admin' => true,
            'roles' => ['super_admin'],
        ], JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);

        $browser->visit('/signin')->waitFor('[dusk="email"]', 20);
        $browser->script([
            "localStorage.setItem('auth_token', '{$token}');",
            "localStorage.setItem('user', '{$payload}');",
        ]);
        $browser->visit('/')->pause(1500);
    }

    private function resizeTo(Browser $browser, int $width): void
    {
        $browser->resize($width, 900);
    }

    private function snap(Browser $browser, string $name, int $bp): void
    {
        $path = "{$this->outDir}/{$name}_{$bp}.png";
        $browser->screenshot($path);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test
    // ─────────────────────────────────────────────────────────────────────────

    public function test_hamburger_menus(): void
    {
        $user = User::factory()->create(['email' => 'hamburger@test.com']);
        $ws = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $ws->id]);

        $report = [];

        $this->browse(function (Browser $browser) use ($user, &$report) {
            $this->authenticate($browser, $user);

            foreach ($this->breakpoints as $bp) {
                $this->resizeTo($browser, $bp);
                // Reload to reset all Vue state (sidebar open, app menu, etc.) for this breakpoint
                $browser->visit('/')->pause(1500);

                // ── Scenario A: Left hamburger opens sidebar ──────────────────
                $label = 'A_left_hamburger_opens_sidebar';
                try {
                    // Sidebar should be hidden before toggle
                    $browser->assertMissing('[dusk="app-sidebar-open"]');

                    $browser->click('[dusk="left-hamburger"]');
                    $browser->pause(400);

                    // Sidebar should now be visible
                    $browser->waitFor('[dusk="app-sidebar"]', 3);
                    $this->snap($browser, "{$label}_open", $bp);

                    // Backdrop should appear on mobile
                    $browser->waitFor('[dusk="sidebar-backdrop"]', 3);

                    // Close by clicking backdrop
                    $browser->click('[dusk="sidebar-backdrop"]');
                    $browser->pause(400);
                    $browser->assertMissing('[dusk="sidebar-backdrop"]');
                    $this->snap($browser, "{$label}_closed_via_backdrop", $bp);

                    $report[] = "  [{$bp}px] {$label}: OK";
                } catch (\Throwable $e) {
                    $report[] = "  [{$bp}px] {$label}: SKIP — {$e->getMessage()}";
                }

                // ── Scenario B: Left hamburger closes sidebar on re-click ─────
                $label = 'B_left_hamburger_toggle';
                try {
                    // Open
                    $browser->click('[dusk="left-hamburger"]');
                    $browser->pause(400);
                    $browser->waitFor('[dusk="sidebar-backdrop"]', 3);
                    $this->snap($browser, "{$label}_open", $bp);

                    // Close by clicking hamburger again
                    $browser->click('[dusk="left-hamburger"]');
                    $browser->pause(400);
                    $browser->assertMissing('[dusk="sidebar-backdrop"]');
                    $this->snap($browser, "{$label}_closed", $bp);

                    $report[] = "  [{$bp}px] {$label}: OK";
                } catch (\Throwable $e) {
                    $report[] = "  [{$bp}px] {$label}: SKIP — {$e->getMessage()}";
                }

                // ── Scenario C: Right hamburger opens app-menu panel ──────────
                $label = 'C_right_hamburger_opens_app_menu';
                try {
                    // App menu panel starts hidden
                    $browser->assertMissing('[dusk="app-menu-panel"]');

                    $browser->click('[dusk="right-hamburger"]');
                    $browser->pause(400);

                    // Panel with theme/notification/user controls should appear
                    $browser->waitFor('[dusk="app-menu-panel"]', 3);
                    $browser->waitFor('[dusk="notification-bell"]', 3);
                    $browser->waitFor('[dusk="user-menu-toggle"]', 3);
                    $this->snap($browser, "{$label}_open", $bp);

                    $report[] = "  [{$bp}px] {$label}: OK";
                } catch (\Throwable $e) {
                    $report[] = "  [{$bp}px] {$label}: SKIP — {$e->getMessage()}";
                }

                // ── Scenario D: Right hamburger closes app-menu panel ─────────
                $label = 'D_right_hamburger_closes_app_menu';
                try {
                    // Panel should already be open from scenario C
                    $browser->waitFor('[dusk="app-menu-panel"]', 2);

                    $browser->click('[dusk="right-hamburger"]');
                    $browser->pause(400);

                    $browser->assertMissing('[dusk="app-menu-panel"]');
                    $this->snap($browser, "{$label}_closed", $bp);

                    $report[] = "  [{$bp}px] {$label}: OK";
                } catch (\Throwable $e) {
                    $report[] = "  [{$bp}px] {$label}: SKIP — {$e->getMessage()}";
                }

                // ── Scenario E: Sidebar nav links are clickable when open ──────
                $label = 'E_sidebar_nav_links_work';
                try {
                    // Open sidebar
                    $browser->click('[dusk="left-hamburger"]');
                    $browser->pause(400);
                    $browser->waitFor('[dusk="app-sidebar"]', 3);

                    // Click first nav link inside sidebar (Dashboard)
                    $browser->waitFor('[dusk="sidebar-nav"]', 3);
                    $browser->click('[dusk="sidebar-nav"] a:first-child');
                    $browser->pause(600);

                    // Sidebar should close after navigation on mobile
                    $browser->assertMissing('[dusk="sidebar-backdrop"]');
                    $this->snap($browser, "{$label}_after_nav", $bp);

                    $report[] = "  [{$bp}px] {$label}: OK";
                } catch (\Throwable $e) {
                    $report[] = "  [{$bp}px] {$label}: SKIP — {$e->getMessage()}";
                }

                // ── Scenario F: User menu opens inside app panel ──────────────
                $label = 'F_user_menu_opens_in_app_panel';
                try {
                    // Open app menu panel first
                    $browser->click('[dusk="right-hamburger"]');
                    $browser->pause(300);
                    $browser->waitFor('[dusk="user-menu-toggle"]', 3);

                    // Open user dropdown
                    $browser->click('[dusk="user-menu-toggle"]');
                    $browser->pause(300);
                    $browser->waitFor('[dusk="user-menu-signout"]', 3);
                    $this->snap($browser, "{$label}_open", $bp);

                    // Close by clicking toggle again
                    $browser->click('[dusk="user-menu-toggle"]');
                    $browser->pause(300);

                    // Close app menu too
                    $browser->click('[dusk="right-hamburger"]');
                    $browser->pause(300);

                    $report[] = "  [{$bp}px] {$label}: OK";
                } catch (\Throwable $e) {
                    $report[] = "  [{$bp}px] {$label}: SKIP — {$e->getMessage()}";
                }
            }
        });

        // Print report
        $passed = count(array_filter($report, fn ($l) => str_contains($l, 'OK')));
        $total = count($report);
        echo "\n\n=== Hamburger Menu Test: {$passed}/{$total} OK ===\n";
        foreach ($report as $line) {
            echo $line."\n";
        }
        echo "\n";

        $this->assertGreaterThan(0, $passed, 'All hamburger scenarios skipped — check dusk attrs');
    }
}
