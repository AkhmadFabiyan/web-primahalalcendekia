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
        Schema::create('api_consumers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type')->default('INTERNAL')->comment('INTERNAL, CLIENT, or PARTNER');
            $table->uuid('client_id')->nullable()->comment('If scoped to a specific client');
            $table->uuid('partner_id')->nullable()->comment('If scoped to a specific partner');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Note: client_id and partner_id are foreign keys, but we'll let them be nullable UUIDs for now
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_consumers');
    }
};
