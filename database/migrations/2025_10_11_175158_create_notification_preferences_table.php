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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Canal preferences
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(false);

            // Task notifications
            $table->boolean('task_assigned_in_app')->default(true);
            $table->boolean('task_assigned_email')->default(true);
            $table->boolean('task_assigned_push')->default(false);

            $table->boolean('task_due_soon_in_app')->default(true);
            $table->boolean('task_due_soon_email')->default(true);
            $table->boolean('task_due_soon_push')->default(true);

            $table->boolean('task_completed_in_app')->default(true);
            $table->boolean('task_completed_email')->default(false);
            $table->boolean('task_completed_push')->default(false);

            // Comment notifications
            $table->boolean('mentioned_in_comment_in_app')->default(true);
            $table->boolean('mentioned_in_comment_email')->default(true);
            $table->boolean('mentioned_in_comment_push')->default(true);

            $table->boolean('comment_added_in_app')->default(true);
            $table->boolean('comment_added_email')->default(false);
            $table->boolean('comment_added_push')->default(false);

            // Project notifications
            $table->boolean('project_updated_in_app')->default(true);
            $table->boolean('project_updated_email')->default(false);
            $table->boolean('project_updated_push')->default(false);

            // Deadline notifications
            $table->boolean('deadline_approaching_in_app')->default(true);
            $table->boolean('deadline_approaching_email')->default(true);
            $table->boolean('deadline_approaching_push')->default(true);

            // Document notifications
            $table->boolean('document_uploaded_in_app')->default(true);
            $table->boolean('document_uploaded_email')->default(false);
            $table->boolean('document_uploaded_push')->default(false);

            // Digest settings
            $table->enum('digest_frequency', ['none', 'daily', 'weekly'])->default('none');
            $table->time('digest_time')->default('09:00:00');
            $table->tinyInteger('digest_day_of_week')->default(1);

            // Quiet hours
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->default('22:00:00');
            $table->time('quiet_hours_end')->default('08:00:00');

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
