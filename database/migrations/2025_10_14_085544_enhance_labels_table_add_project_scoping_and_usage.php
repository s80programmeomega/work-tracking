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
            // Drop indexes first
            $table->dropIndex(['labels_projet_id_index']);
            $table->dropIndex(['labels_is_global_index']);
            $table->dropIndex(['labels_projet_id_is_global_index']);

            // Drop foreign keys
            $table->dropForeign(['projet_id']);
            $table->dropForeign(['created_by']);

            // Drop columns
            $table->dropColumn(['projet_id', 'is_global', 'usage_count', 'created_by']);
        });
    }
};
