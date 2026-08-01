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
        Schema::create('audit_findings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('audit_execution_id')->constrained('audit_executions');
            $table->foreignUuid('project_id')->constrained('projects');
            $table->string('finding_number');
            $table->text('description');
            $table->boolean('evidence_required')->default(false);
            $table->string('status');
            
            $table->foreignUuid('reported_by')->constrained('users');
            $table->timestamp('reported_at');
            
            $table->text('resolution_notes')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();

            $table->unique(['audit_execution_id', 'finding_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_findings');
    }
};
