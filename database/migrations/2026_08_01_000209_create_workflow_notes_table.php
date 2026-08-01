<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('workflow_step_id')->nullable()->constrained('workflow_steps')->cascadeOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('author_id')->constrained('users');
            $table->string('note_type')->default('WORK_NOTE');
            $table->text('content');
            $table->boolean('is_client_visible')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_notes');
    }
};
