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
        // PG-07 : fiches pratiques en wolof, en texte et en audio, saisies par l'équipe.
        Schema::table('journey_steps', function (Blueprint $table) {
            $table->string('title_wo')->nullable()->after('body');
            $table->text('body_wo')->nullable()->after('title_wo');
            $table->string('audio_path')->nullable()->after('body_wo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journey_steps', function (Blueprint $table) {
            $table->dropColumn(['title_wo', 'body_wo', 'audio_path']);
        });
    }
};
