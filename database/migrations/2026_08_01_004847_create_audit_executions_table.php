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
        Schema::create('audit_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects');
            $table->foreignUuid('audit_plan_id')->constrained('audit_plans');
            $table->foreignUuid('task_id')->constrained('tasks');
            $table->text('summary')->nullable();
            $table->boolean('has_findings')->nullable();
            
            $table->foreignUuid('started_by')->constrained('users');
            $table->timestamp('started_at');
            
            $table->foreignUuid('submitted_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            
            $table->timestamps();

            $table->unique('audit_plan_id');
            $table->unique('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_executions');
    }
};
