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
        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('api_consumer_id');
            $table->string('method');
            $table->string('endpoint');
            $table->string('idempotency_key');
            $table->string('request_hash');
            $table->integer('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->string('resource_type')->nullable();
            $table->uuid('resource_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['api_consumer_id', 'idempotency_key'], 'api_consumer_idempotency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
    }
};
