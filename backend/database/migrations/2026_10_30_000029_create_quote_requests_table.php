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
        // SE-02 : demande de devis à un expert et suivi de la prestation.
        // Le règlement se fait directement entre le client et l'expert (RG-09).
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('expert_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->string('budget', 120)->nullable();
            $table->date('wanted_by')->nullable();
            $table->string('status', 20);
            $table->unsignedBigInteger('quote_amount_fcfa')->nullable();
            $table->string('quote_delay', 120)->nullable();
            $table->text('quote_details')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['expert_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
