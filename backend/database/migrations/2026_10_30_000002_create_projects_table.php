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
        Schema::create('projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            // Résumé public, sans secret (section 8, deux couches d'information).
            $table->text('summary');
            // Détails privés, visibles seulement avec l'accord du porteur.
            $table->text('details')->nullable();
            $table->string('region')->nullable();
            $table->string('stage', 20)->default('idee');
            $table->string('status', 20)->default('actif')->index();
            $table->unsignedTinyInteger('readiness_score')->default(0);
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // Chaque version est datée et scellée par une empreinte (preuve d'antériorité).
        Schema::create('project_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->json('content');
            $table->char('fingerprint', 64);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_versions');
        Schema::dropIfExists('projects');
    }
};
