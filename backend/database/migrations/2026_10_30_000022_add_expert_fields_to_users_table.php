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
        // SE-01 : services proposés et tarif indicatif des experts.
        Schema::table('users', function (Blueprint $table) {
            $table->string('expertise', 300)->nullable()->after('bio');
            $table->string('rate', 120)->nullable()->after('expertise');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['expertise', 'rate']);
        });
    }
};
