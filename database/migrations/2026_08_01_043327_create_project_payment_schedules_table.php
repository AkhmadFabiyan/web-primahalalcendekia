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
        Schema::create('project_payment_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->integer('sequence');
            $table->string('invoice_type');
            $table->decimal('client_amount', 15, 2);
            $table->decimal('partner_amount', 15, 2)->nullable();
            $table->string('status')->default('PENDING'); // PENDING, INVOICED, dll
            
            $table->timestamps();
            
            $table->unique(['project_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_payment_schedules');
    }
};
