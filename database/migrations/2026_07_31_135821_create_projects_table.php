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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('source_lead_id')->unique();
            $table->foreign('source_lead_id')->references('id')->on('leads')->restrictOnDelete();
            
            $table->uuid('client_id')->unique();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            
            $table->string('project_name');
            $table->string('service_type');
            $table->decimal('client_nominal', 15, 2);
            $table->decimal('partner_nominal', 15, 2)->nullable();
            
            $table->string('payment_scheme');
            $table->integer('installment_count')->default(1);
            
            $table->string('status')->index();
            
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
