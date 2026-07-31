<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('assigned_to');
            $table->string('assignment_role', 50);
            $table->string('task_type', 100);
            $table->string('task_key', 200);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('MEDIUM');
            $table->string('status', 50)->default('TODO');
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users');

            // Unique constraint for idempotency
            $table->unique(['project_id', 'task_key']);

            // Recommended indexes
            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');
            $table->index('entered_at');
            $table->index('deadline');
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
