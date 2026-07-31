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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('project_id')->index();
            $table->foreign('project_id')->references('id')->on('projects')->restrictOnDelete();
            
            $table->string('invoice_number')->unique()->nullable();
            $table->string('invoice_type')->index();
            $table->uuid('billing_group_id')->index();
            $table->string('audience');
            
            $table->uuid('partner_id')->nullable()->index();
            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
            
            $table->json('billing_snapshot')->nullable();
            $table->integer('sequence')->default(1);
            
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->storedAs('subtotal - discount_total');
            
            $table->string('status')->index();
            
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(
                ['project_id', 'invoice_type', 'sequence', 'audience'],
                'invoices_unique_combination'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
