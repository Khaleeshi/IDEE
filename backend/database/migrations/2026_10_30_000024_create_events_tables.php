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
        // EV-01 : sessions de pitch en ligne et en présentiel.
        Schema::create('events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organisation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description');
            $table->string('mode', 20);
            $table->dateTime('starts_at');
            $table->string('place', 300)->nullable();
            $table->unsignedSmallInteger('pitch_slots')->default(0);
            $table->date('apply_until')->nullable();
            $table->string('status', 20)->default('publie');
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20);
            $table->string('status', 20);
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
    }
};
