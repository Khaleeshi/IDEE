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
        // PR-07 : part envisagée de chaque personne de l'équipe, indicative.
        Schema::table('project_members', function (Blueprint $table) {
            $table->decimal('share_percent', 5, 2)->nullable()->after('role');
        });

        // PR-08 et RG-12 : rapprochement proposé seulement entre porteurs volontaires.
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('open_to_matching')->default(false)->after('status');
        });

        Schema::create('project_matches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('target_project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('message', 500)->nullable();
            $table->string('status', 20)->default('proposee');
            $table->foreignUlid('contact_request_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'target_project_id']);
        });

        // CF-05 : accord de confidentialité accepté avant l'accès aux documents.
        Schema::table('document_accesses', function (Blueprint $table) {
            $table->string('nda_version', 20)->nullable()->after('reason');
            $table->timestamp('nda_accepted_at')->nullable()->after('nda_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_accesses', function (Blueprint $table) {
            $table->dropColumn(['nda_version', 'nda_accepted_at']);
        });

        Schema::dropIfExists('project_matches');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('open_to_matching');
        });

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropColumn('share_percent');
        });
    }
};
