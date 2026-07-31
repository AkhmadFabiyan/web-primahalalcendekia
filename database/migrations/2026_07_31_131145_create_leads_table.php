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
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Informasi Perusahaan
            $table->string('company_name')->index();
            $table->string('business_sector')->nullable();
            
            // Lokasi
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            
            // Kontak PIC
            $table->string('pic_name');
            $table->string('pic_phone');
            $table->string('pic_email')->nullable();
            
            // Tipe Klien & Partner
            $table->string('client_type')->index();
            $table->uuid('partner_id')->nullable()->index();
            $table->foreign('partner_id')->references('id')->on('partners')->nullOnDelete();
            
            // Data Partner Baru (jika belum ada)
            $table->string('partner_name')->nullable();
            $table->string('partner_pic_name')->nullable();
            $table->string('partner_phone')->nullable();
            $table->string('partner_email')->nullable();
            
            // Layanan & Nominal
            $table->string('service_type')->nullable();
            $table->decimal('client_nominal', 15, 2);
            $table->decimal('partner_nominal', 15, 2)->nullable();
            
            // Skema Pembayaran
            $table->string('payment_scheme')->nullable();
            $table->integer('installment_count')->default(1);
            
            // Informasi Marketing & Tambahan
            $table->uuid('marketing_id')->index();
            $table->foreign('marketing_id')->references('id')->on('users')->restrictOnDelete();
            $table->string('lead_source')->nullable();
            $table->text('notes')->nullable();
            
            // Status
            $table->string('status')->index();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
