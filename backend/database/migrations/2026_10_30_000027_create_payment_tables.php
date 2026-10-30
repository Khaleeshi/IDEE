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
        // PA-01 et PA-03 : achats de packs prépayés, payés par Wave ou par Orange Money, avec leur reçu.
        // Seul l'argent dû à IDEE pour ses propres offres est encaissé : jamais celui d'un tiers.
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('offer', 30);
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('amount_fcfa');
            $table->string('method', 30);
            $table->string('status', 20);
            $table->string('provider_reference')->nullable()->index();
            $table->string('launch_url', 1000)->nullable();
            $table->string('payer_reference', 100)->nullable();
            $table->string('receipt_number', 30)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'method']);
        });

        // Droits ouverts par un achat : offre Pro, offre investisseur, mise en avant d'un projet.
        Schema::create('entitlements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('offer', 30);
            $table->foreignUlid('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['offer', 'ends_at']);
        });

        // PA-03 : factures des contrats avec les organisations (espace organisations, sur devis).
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('number', 30)->unique();
            $table->foreignUlid('organisation_id')->constrained()->cascadeOnDelete();
            $table->string('label', 300);
            $table->unsignedBigInteger('amount_fcfa');
            $table->date('due_on')->nullable();
            $table->string('status', 20);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Statistiques de visibilité de l'offre Pro : vues de la fiche par jour.
        Schema::create('project_views', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('views')->default(0);

            $table->unique(['project_id', 'day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_views');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('entitlements');
        Schema::dropIfExists('payments');
    }
};
