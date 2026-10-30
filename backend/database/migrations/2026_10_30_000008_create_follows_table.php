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
        // Suivre un projet, une personne ou un secteur (FC-02).
        Schema::create('follows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('followed_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
            $table->unique(['user_id', 'followed_id']);
            $table->unique(['user_id', 'sector_id']);
        });

        // Type d'apport d'une réponse à un besoin : « Je peux aider » ou « Je connais quelqu'un » (FC-03).
        Schema::table('help_offers', function (Blueprint $table) {
            $table->string('kind', 20)->default('aide')->after('user_id');
            $table->dropUnique(['need_id', 'user_id']);
            $table->unique(['need_id', 'user_id', 'kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('help_offers', function (Blueprint $table) {
            $table->dropUnique(['need_id', 'user_id', 'kind']);
            $table->unique(['need_id', 'user_id']);
            $table->dropColumn('kind');
        });

        Schema::dropIfExists('follows');
    }
};
