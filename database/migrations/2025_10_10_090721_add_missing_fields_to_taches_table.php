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
        // Only rename columns (other fields already exist from previous migration)
        \DB::statement('ALTER TABLE taches CHANGE ordre position INT(11) NULL DEFAULT NULL');
        \DB::statement('ALTER TABLE taches CHANGE image_couverture cover_image VARCHAR(255) NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back using raw SQL
        \DB::statement('ALTER TABLE taches CHANGE position ordre INT(11) NULL DEFAULT NULL');
        \DB::statement('ALTER TABLE taches CHANGE cover_image image_couverture VARCHAR(255) NULL DEFAULT NULL');
    }
};
