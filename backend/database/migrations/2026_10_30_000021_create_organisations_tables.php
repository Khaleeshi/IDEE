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
        // CP-05 : pages d'organisation gérées par plusieurs membres.
        // CF-03 : vérification des entreprises par leur RCCM et leur NINEA.
        Schema::create('organisations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 150);
            $table->string('kind', 30);
            $table->text('description');
            $table->string('region', 100)->nullable();
            $table->string('website', 300)->nullable();
            $table->string('rccm', 50)->nullable();
            $table->string('ninea', 30)->nullable();
            $table->string('verification_status', 20)->default('non_verifiee');
            $table->string('verification_document_path')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamp('verification_requested_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('verification_status');
        });

        Schema::create('organisation_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamps();

            $table->unique(['organisation_id', 'user_id']);
        });

        // OR-01 : programmes publiés par les organisations vérifiées.
        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignUlid('organisation_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organisation_id');
        });

        Schema::dropIfExists('organisation_members');
        Schema::dropIfExists('organisations');
    }
};
