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
        // Demandes de contact soumises à la règle « aucune demande sans réponse ».
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('need_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->string('status', 20)->default('envoyee')->index();
            $table->text('refusal_reason')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['sender_id', 'created_at']);
            $table->index(['recipient_id', 'status']);
        });

        // Historique des changements de statut, base de l'indicateur de réactivité.
        Schema::create('contact_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('contact_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_request_events');
        Schema::dropIfExists('contact_requests');
    }
};
