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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('business_id')->unique(); // PHC-HAL-YYYY-XXXX
            $table->string('client_type')->index(); // DIRECT, PARTNER
            $table->foreignUuid('partner_id')->nullable()->constrained('partners')->onDelete('restrict');
            
            $table->string('company_name')->index();
            $table->string('company_type')->nullable();
            $table->string('business_sector')->nullable();
            
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            
            $table->string('pic_name');
            $table->string('pic_phone')->index();
            $table->string('pic_email')->index();
            
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
