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
        // RR-04 : alertes enregistrées sur de nouveaux projets.
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 200);
            $table->json('filters');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });

        // OP-02 : critères d'éligibilité structurés (vides = ouverts à tous).
        Schema::table('opportunities', function (Blueprint $table) {
            $table->json('stages')->nullable()->after('criteria');
            $table->json('sectors')->nullable()->after('stages');
            $table->unsignedBigInteger('max_amount_fcfa')->nullable()->after('sectors');
        });

        // OP-03 : dernier rappel envoyé avant la date limite (en jours).
        Schema::table('opportunity_applications', function (Blueprint $table) {
            $table->unsignedSmallInteger('deadline_alert_days')->nullable()->after('reminded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunity_applications', function (Blueprint $table) {
            $table->dropColumn('deadline_alert_days');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['stages', 'sectors', 'max_amount_fcfa']);
        });

        Schema::dropIfExists('saved_searches');
    }
};
