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
        // Section 8 : la charte communautaire est acceptée à l'inscription ; on garde la version acceptée.
        Schema::table('users', function (Blueprint $table) {
            $table->string('terms_version', 20)->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['terms_version', 'terms_accepted_at']));
    }
};
