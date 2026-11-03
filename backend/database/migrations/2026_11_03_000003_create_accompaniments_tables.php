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
        // AC-01 : accompagnement par un coach ou un mentor, bénévole ou de mentorat.
        Schema::create('accompaniments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('member_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20);
            $table->string('status', 20);
            $table->text('objectives');
            $table->text('decline_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['coach_id', 'status']);
            $table->index(['requester_id', 'status']);
        });

        // AC-03 : points de suivi datés, écrits par l'un ou l'autre.
        Schema::create('accompaniment_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('accompaniment_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('held_on');
            $table->text('summary');
            $table->text('next_steps')->nullable();
            $table->timestamps();
        });

        // AV-01 : avis laissé après un accompagnement terminé ou une prestation confirmée.
        Schema::create('reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('accompaniment_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignUlid('quote_request_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index('reviewee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('accompaniment_sessions');
        Schema::dropIfExists('accompaniments');
    }
};
