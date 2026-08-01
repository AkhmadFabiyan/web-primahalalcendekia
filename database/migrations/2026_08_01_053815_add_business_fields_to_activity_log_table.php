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
        Schema::table('activity_log', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_log', 'project_id')) {
                $table->uuid('project_id')->nullable();
                $table->index(['project_id', 'created_at']);
            }
            
            if (!Schema::hasColumn('activity_log', 'is_client_visible')) {
                $table->boolean('is_client_visible')->default(false);
                $table->index(['project_id', 'is_client_visible', 'created_at']);
            }

            if (!Schema::hasColumn('activity_log', 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->index();
            }

            // Create index for log_name and created_at if not exists
            $table->index(['log_name', 'created_at']);
        });

        // Backfill data
        \Illuminate\Support\Facades\DB::table('activity_log')
            ->whereNotNull('attribute_changes')
            ->orderBy('id')
            ->chunk(100, function ($logs) {
                foreach ($logs as $log) {
                    $attr = json_decode($log->attribute_changes, true);
                    $props = $log->properties ? json_decode($log->properties, true) : [];
                    
                    if (is_array($attr)) {
                        $props = array_merge($props, $attr);
                    }
                    
                    \Illuminate\Support\Facades\DB::table('activity_log')
                        ->where('id', $log->id)
                        ->update(['properties' => json_encode($props)]);
                }
            });

        // Do not drop attribute_changes yet
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'created_at']);
            $table->dropIndex(['project_id', 'is_client_visible', 'created_at']);
            $table->dropIndex(['log_name', 'created_at']);
            $table->dropColumn(['project_id', 'is_client_visible', 'batch_uuid']);
        });
    }
};
