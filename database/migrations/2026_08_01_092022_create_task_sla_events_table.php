<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_sla_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_sla_cycle_id')->constrained('task_sla_cycles')->cascadeOnDelete();
            $table->string('event_type'); // STARTED, REMINDER_SENT, PAUSED, RESUMED, BREACHED, ESCALATED_LEVEL_1, ESCALATED_LEVEL_2, COMPLETED, DEADLINE_ADJUSTED, CANCELLED
            $table->integer('escalation_level')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignUuid('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deduplication_key')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_sla_events');
    }
};

