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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects');
            $table->string('step_code');
            $table->string('workflow_lane'); // A, B, FINAL
            $table->string('track_code')->nullable(); // ENTRY, COMPANION, AUDITOR
            $table->string('status');
            $table->boolean('is_required')->default(true);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('last_changed_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['project_id', 'step_code']);
            // A project can only have one active tracker step per track_code (for the main trackers)
            $table->unique(['project_id', 'track_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
