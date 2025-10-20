<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projets', function (Blueprint $table) {
            // Check and add missing columns
            if (!Schema::hasColumn('projets', 'code')) {
                $table->string('code')->unique()->after('description');
            }

            if (!Schema::hasColumn('projets', 'visibility')) {
                $table->enum('visibility', ['public', 'private', 'team'])->default('team')->after('status');
            }

            if (!Schema::hasColumn('projets', 'couleur')) {
                $table->string('couleur')->default('#3B82F6')->after('visibility');
            }

            if (!Schema::hasColumn('projets', 'progression')) {
                $table->integer('progression')->default(0)->after('budget');
            }

            if (!Schema::hasColumn('projets', 'is_template')) {
                $table->boolean('is_template')->default(false)->after('progression');
            }

            if (!Schema::hasColumn('projets', 'is_favorite')) {
                $table->boolean('is_favorite')->default(false)->after('is_template');
            }

            if (!Schema::hasColumn('projets', 'objectifs')) {
                $table->text('objectifs')->nullable()->after('is_favorite');
            }

            if (!Schema::hasColumn('projets', 'metadata')) {
                $table->json('metadata')->nullable()->after('objectifs');
            }

            if (!Schema::hasColumn('projets', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('metadata');
            }

            if (!Schema::hasColumn('projets', 'deleted_at')) {
                $table->softDeletes();
            }

            // Update status enum if needed (from old values to new values)
            DB::statement("ALTER TABLE projets MODIFY COLUMN status ENUM('active', 'archived', 'completed') DEFAULT 'active'");
        });

        // Generate codes for existing projects
        DB::statement("
            UPDATE projets
            SET code = CONCAT('PROJ-', LPAD(id, 4, '0'))
            WHERE code IS NULL OR code = ''
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projets', function (Blueprint $table) {
            $table->dropColumn([
                'visibility',
                'couleur',
                'progression',
                'is_template',
                'is_favorite',
                'objectifs',
                'metadata',
                'archived_at',
            ]);

            $table->dropSoftDeletes();
        });
    }
};
