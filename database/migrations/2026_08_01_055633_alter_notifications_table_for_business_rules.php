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
        // For SQLite compatibility, we will drop the table and recreate it since it's empty
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            
            // Custom Columns
            $table->uuid('project_id')->nullable();
            $table->string('priority')->default('MEDIUM');
            $table->string('event_code')->nullable();
            $table->string('deduplication_key')->nullable()->unique();
            $table->timestamp('archived_at')->nullable();
            
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['notifiable_type', 'notifiable_id', 'archived_at', 'read_at'], 'notifiable_archived_read_idx');
            $table->index(['project_id', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index(['event_code', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        
        // Revert to original
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
};
