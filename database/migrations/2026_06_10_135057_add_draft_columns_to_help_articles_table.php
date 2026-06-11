<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_articles', function (Blueprint $table) {
            $table->longText('draft_body_fr')->nullable()->after('body_en');
            $table->longText('draft_body_en')->nullable()->after('draft_body_fr');
            $table->timestamp('draft_saved_at')->nullable()->after('draft_body_en');
        });
    }

    public function down(): void
    {
        Schema::table('help_articles', function (Blueprint $table) {
            $table->dropColumn(['draft_body_fr', 'draft_body_en', 'draft_saved_at']);
        });
    }
};
