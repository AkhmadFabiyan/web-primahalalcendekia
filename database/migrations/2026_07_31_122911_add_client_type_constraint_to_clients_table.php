<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add CHECK constraint to enforce business logic:
        // DIRECT -> partner_id IS NULL
        // PARTNER -> partner_id IS NOT NULL
        DB::statement("
            ALTER TABLE clients 
            ADD CONSTRAINT chk_client_type_partner 
            CHECK (
                (client_type = 'DIRECT' AND partner_id IS NULL) OR 
                (client_type = 'PARTNER' AND partner_id IS NOT NULL)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE clients DROP CONSTRAINT chk_client_type_partner");
    }
};
