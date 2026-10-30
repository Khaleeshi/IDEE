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
        // Documents privés d'un projet (PR-05).
        Schema::create('project_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->timestamps();
        });

        // Autorisation d'un membre à consulter les documents, accordée par le porteur et révocable (RG-05).
        Schema::create('document_accesses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // Journal d'accès : demandes, autorisations, refus, révocations et consultations (RG-05).
        Schema::create('document_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 20);
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_logs');
        Schema::dropIfExists('document_accesses');
        Schema::dropIfExists('project_documents');
    }
};
