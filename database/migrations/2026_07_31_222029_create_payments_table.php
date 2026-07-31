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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('invoice_id')->index();
            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            
            $table->string('payment_number')->unique();
            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            
            $table->string('status')->index();
            
            $table->text('verification_notes')->nullable();
            $table->uuid('verified_by')->nullable()->index();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            
            $table->text('rejection_reason')->nullable();
            $table->uuid('rejected_by')->nullable()->index();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
