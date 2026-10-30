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
        // EV-02 : rencontres ciblées en ligne, par binômes et par tours.
        Schema::table('events', function (Blueprint $table) {
            $table->string('format', 20)->default('pitch')->after('title');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('side', 20)->nullable()->after('kind');
        });

        Schema::create('event_pairings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round');
            $table->dateTime('starts_at');
            $table->foreignUlid('first_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('second_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('room_url', 300);
            $table->timestamps();

            $table->index(['event_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_pairings');

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('side');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('format');
        });
    }
};
