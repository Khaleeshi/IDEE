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
        // Inscription par téléphone ou par e-mail (CP-01) : le numéro devient facultatif.
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
        });

        // Un code de connexion vise un numéro ou une adresse e-mail.
        Schema::table('one_time_codes', function (Blueprint $table) {
            $table->renameColumn('phone', 'identifier');
        });

        Schema::table('one_time_codes', function (Blueprint $table) {
            $table->string('identifier', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('one_time_codes', function (Blueprint $table) {
            $table->renameColumn('identifier', 'phone');
        });
    }
};
