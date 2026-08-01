<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('task_type');
            $table->string('name');
            $table->integer('duration_value');
            $table->string('duration_unit'); // MINUTES, HOURS, BUSINESS_DAYS, SCHEDULED_DATE
            $table->integer('reminder_before_minutes')->nullable();
            $table->integer('first_escalation_after_minutes')->nullable();
            $table->integer('second_escalation_after_minutes')->nullable();
            $table->boolean('uses_business_calendar')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};

