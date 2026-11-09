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
        // IN-02 : coach ou mentor de la structure affecté à un projet incubé.
        Schema::table('cohort_projects', function (Blueprint $table) {
            $table->foreignUlid('coach_id')->nullable()->after('project_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cohort_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coach_id');
        });
    }
};
