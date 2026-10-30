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
        // Parcours guidés : un parcours par situation de départ (PG-01 à PG-04).
        Schema::create('journeys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug')->unique();
            $table->string('situation');
            $table->string('situation_hint')->nullable();
            $table->string('title');
            $table->text('summary');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // Étapes d'un parcours, chacune avec sa fiche pratique sourcée et datée (PG-06, RG-10).
        Schema::create('journey_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('journey_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('title');
            $table->text('body');
            $table->json('documents')->nullable();
            $table->string('organisation')->nullable();
            $table->string('cost')->nullable();
            $table->string('delay')->nullable();
            $table->string('source_label')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->date('checked_on')->nullable();
            $table->boolean('needs_expert')->default(false);
            $table->string('action', 40)->nullable();
            $table->foreignUlid('related_journey_id')->nullable()->constrained('journeys')->nullOnDelete();
            $table->timestamps();

            $table->unique(['journey_id', 'slug']);
        });

        // Avancement personnel d'un membre, étape par étape (PG-05).
        Schema::create('step_progress', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('journey_step_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('a_faire');
            $table->text('note')->nullable();
            $table->date('remind_on')->nullable()->index();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'journey_step_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('step_progress');
        Schema::dropIfExists('journey_steps');
        Schema::dropIfExists('journeys');
    }
};
