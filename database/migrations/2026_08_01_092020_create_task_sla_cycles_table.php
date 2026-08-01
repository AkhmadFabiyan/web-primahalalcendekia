<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_sla_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->integer('cycle_number')->default(1);
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
            $table->json('duration_snapshot')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->integer('total_paused_minutes')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('breached_at')->nullable();
            $table->integer('current_escalation_level')->default(0);
            $table->timestamp('last_escalated_at')->nullable();
            $table->string('status'); // ACTIVE, PAUSED, MET, BREACHED, CANCELLED
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_sla_cycles');
    }
};

