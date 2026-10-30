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
        // Journal de progression : mises à jour datées publiées par le porteur (PR-04).
        Schema::create('project_updates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // Fil d'activité : nouveaux projets, besoins, mises à jour, réussites (FC-01).
        Schema::create('activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type', 30);
            $table->foreignUlid('actor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('need_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_update_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('project_updates');
    }
};
