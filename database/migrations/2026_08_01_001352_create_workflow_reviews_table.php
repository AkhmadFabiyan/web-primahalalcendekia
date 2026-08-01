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
        Schema::create('workflow_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->uuid('workflow_step_id')->index();
            $table->uuid('submission_history_id')->unique();
            $table->uuid('entry_task_id')->index();
            $table->uuid('review_task_id')->unique();
            $table->uuid('reviewer_id')->nullable()->index();
            $table->string('decision');
            $table->text('reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_reviews');
    }
};
