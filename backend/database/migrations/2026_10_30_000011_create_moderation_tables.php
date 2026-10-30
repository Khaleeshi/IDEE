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
        // Administration (AD-01 à AD-04) et sanctions de modération.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('verification_level');
            $table->timestamp('suspended_until')->nullable()->after('is_admin');
            $table->timestamp('banned_at')->nullable()->after('suspended_until');
        });

        // Signalements et décisions de modération, avec un recours possible (CF-04, AD-01).
        Schema::create('reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->ulidMorphs('reportable');
            $table->foreignUlid('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->unsignedTinyInteger('priority')->default(1);
            $table->string('status', 20)->default('ouvert')->index();
            $table->string('decision', 20)->nullable();
            $table->text('decision_note')->nullable();
            // Contenu retiré, gardé pour pouvoir le rétablir si le recours aboutit.
            $table->text('removed_content')->nullable();
            $table->foreignUlid('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('appeal_text')->nullable();
            $table->timestamp('appealed_at')->nullable();
            $table->foreignUlid('appeal_moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('appeal_upheld')->nullable();
            $table->timestamp('appeal_decided_at')->nullable();
            $table->timestamps();
        });

        // Vérifications d'identité des investisseurs et des experts (CF-02, AD-04).
        Schema::create('verifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('provider', 30)->default('manuel');
            $table->string('status', 20)->default('en_attente')->index();
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->text('refusal_reason')->nullable();
            $table->foreignUlid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Un message retiré par la modération reste conservé pour l'examen d'un recours.
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable()->after('read_at');
        });

        // Une fiche modifiée dans l'administration n'est plus écrasée à la mise en ligne (AD-02).
        Schema::table('journey_steps', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('related_journey_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journey_steps', fn (Blueprint $table) => $table->dropColumn('edited_at'));
        Schema::table('messages', fn (Blueprint $table) => $table->dropColumn('removed_at'));
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('reports');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_admin', 'suspended_until', 'banned_at']));
    }
};
