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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('source_system')->nullable()->after('status')->comment('Sistem sumber saat diimpor');
            $table->string('external_reference')->nullable()->after('source_system')->comment('Referensi unik dari sistem sumber');
            
            $table->unique(['source_system', 'external_reference'], 'leads_source_external_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique('leads_source_external_unique');
            $table->dropColumn(['source_system', 'external_reference']);
        });
    }
};
