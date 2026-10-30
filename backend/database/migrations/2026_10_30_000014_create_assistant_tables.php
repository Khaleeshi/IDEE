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
        // Questions de l'assistant pour structurer l'idée, et réponses du porteur (IA-01).
        Schema::create('idea_clarifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->json('questions')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('answers')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // Business plan généré puis modifiable, versionné (IA-02).
        Schema::create('business_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('status', 20);
            $table->json('sections')->nullable();
            $table->json('assumptions')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_plans');
        Schema::dropIfExists('idea_clarifications');
    }
};
