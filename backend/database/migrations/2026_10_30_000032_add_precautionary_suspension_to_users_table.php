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
        // Section 8 : suspension préventive après plusieurs signalements, levée par un modérateur.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('precautionary_suspension_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('precautionary_suspension_at'));
    }
};
