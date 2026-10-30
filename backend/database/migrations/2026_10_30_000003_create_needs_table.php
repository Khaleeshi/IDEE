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
        // Appels à action : chaque besoin d'un projet est publié séparément.
        Schema::create('needs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title');
            $table->text('description');
            // Montant indicatif : aucun argent ne transite par la plateforme (RG-09).
            $table->unsignedBigInteger('amount_fcfa')->nullable();
            $table->string('profile_sought')->nullable();
            $table->date('deadline')->nullable();
            $table->text('counterpart')->nullable();
            $table->string('status', 20)->default('ouvert')->index();
            $table->text('outcome')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // Réponses « Je peux aider » à un besoin précis.
        Schema::create('help_offers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('need_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('status', 20)->default('proposee');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['need_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_offers');
        Schema::dropIfExists('needs');
    }
};
