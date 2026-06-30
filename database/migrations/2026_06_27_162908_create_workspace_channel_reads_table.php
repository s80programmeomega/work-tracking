<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_channel_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('workspace_channel_id')->constrained('workspace_channels')->onDelete('cascade');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'workspace_channel_id']);
            $table->index(['user_id', 'workspace_channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_channel_reads');
    }
};
