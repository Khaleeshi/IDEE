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
        // EQ-01 : une personne de l'équipe peut être un membre d'IDEE, invité à une
        // fonction (chef de projet, coach, comptable…) et qui accepte ou non.
        Schema::table('project_members', function (Blueprint $table) {
            $table->foreignUlid('user_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->string('invitation_status', 20)->nullable()->after('role');
            $table->timestamp('responded_at')->nullable()->after('invitation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['invitation_status', 'responded_at']);
        });
    }
};
