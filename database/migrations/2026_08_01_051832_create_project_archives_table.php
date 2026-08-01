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
        Schema::create('project_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            
            $table->integer('archive_version')->default(1);
            $table->string('status')->index();
            
            $table->timestamp('generated_at')->nullable();
            $table->uuid('generated_by')->nullable();
            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();
            
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_archive_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_archive_id')->index();
            $table->foreign('project_archive_id')->references('id')->on('project_archives')->cascadeOnDelete();
            
            $table->string('source_type')->index();
            $table->uuid('source_id')->index();
            $table->unsignedBigInteger('media_id')->nullable()->index();
            
            $table->string('category')->index();
            $table->string('document_name');
            $table->string('document_version')->nullable();
            
            $table->string('visibility')->index(); // INTERNAL or CLIENT
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum_sha256')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_archive_items');
        Schema::dropIfExists('project_archives');
    }
};
