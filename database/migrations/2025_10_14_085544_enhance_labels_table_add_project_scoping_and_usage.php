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
        Schema::table('labels', function (Blueprint $table) {
            // Add project scoping - NULL means global label
            $table->foreignId('projet_id')->nullable()->after('id')
                ->constrained('projets')->onDelete('cascade');

            // Flag to indicate if label is global or project-specific
            $table->boolean('is_global')->default(true)->after('ordre');

            // Track usage count for analytics
            $table->integer('usage_count')->default(0)->after('is_global');

            // Track who created the label
            $table->foreignId('created_by')->nullable()->after('usage_count')
                ->constrained('users')->onDelete('set null');

            // Add indexes for performance
            $table->index('projet_id');
            $table->index('is_global');
            $table->index(['projet_id', 'is_global']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labels', function (Blueprint $table) {
            $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'labels' AND CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL"))->pluck('CONSTRAINT_NAME');

            foreach (['labels_projet_id_foreign', 'labels_created_by_foreign'] as $fk) {
                if ($foreignKeys->contains($fk)) {
                    $table->dropForeign($fk);
                }
            }

            $indexes = collect(DB::select('SHOW INDEX FROM labels'))->pluck('Key_name');

            foreach (['labels_projet_id_index', 'labels_is_global_index', 'labels_projet_id_is_global_index'] as $index) {
                if ($indexes->contains($index)) {
                    $table->dropIndex($index);
                }
            }

            $columns = Schema::getColumnListing('labels');
            $toDrop = array_filter(['projet_id', 'is_global', 'usage_count', 'created_by'], fn ($c) => in_array($c, $columns));
            if ($toDrop) {
                $table->dropColumn(array_values($toDrop));
            }
        });
    }
};
