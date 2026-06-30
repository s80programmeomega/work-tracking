<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workspace_message_reactions')) {
            return;
        }

        Schema::create('workspace_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_message_id')->constrained('workspace_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('emoji', 32);
            $table->timestamps();

            $table->unique(['workspace_message_id', 'user_id', 'emoji'], 'wmr_message_user_emoji_unique');
            $table->index('workspace_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_message_reactions');
    }
};
