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
        // Répertoire des programmes, concours et financements (OP-01), géré par l'équipe (AD-02).
        Schema::create('opportunities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('organisation');
            $table->string('kind', 20);
            $table->text('summary');
            $table->text('criteria')->nullable();
            $table->string('amount')->nullable();
            $table->date('deadline')->nullable();
            $table->string('deadline_note')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('source_label')->nullable();
            $table->date('checked_on')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
        });

        // Candidatures suivies par le membre, statut déclaré par lui et rappel (parcours financement, PG-03).
        Schema::create('opportunity_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->date('remind_on')->nullable()->index();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'opportunity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunity_applications');
        Schema::dropIfExists('opportunities');
    }
};
