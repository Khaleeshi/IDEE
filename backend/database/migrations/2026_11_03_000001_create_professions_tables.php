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
        // PM-01 : référentiel des métiers, rangés par catégorie, modifiable depuis
        // l'administration sans toucher au code.
        Schema::create('profession_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name', 120);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('professions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profession_category_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('name', 120);
            $table->string('description', 300)->nullable();
            // Profession réglementée (avocat, notaire, expert-comptable…) : identité vérifiée exigée (PM-05).
            $table->boolean('regulated')->default(false);
            // Un métier désactivé ne se choisit plus, mais les profils existants le gardent (PM-04).
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // PM-02 : profils professionnels d'un membre, plusieurs par compte.
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profession_id')->constrained()->restrictOnDelete();
            $table->string('title', 120)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            // PM-03 : prestations payantes, bénévoles, mentorat, cumulables.
            $table->boolean('offers_paid')->default(false);
            $table->boolean('offers_volunteer')->default(false);
            $table->boolean('offers_mentoring')->default(false);
            $table->string('rate', 120)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'profession_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
        Schema::dropIfExists('professions');
        Schema::dropIfExists('profession_categories');
    }
};
