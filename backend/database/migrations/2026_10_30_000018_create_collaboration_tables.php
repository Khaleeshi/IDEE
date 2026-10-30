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
        // CO-01 : accord de prochaine étape conclu dans une conversation.
        Schema::create('agreements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contact_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('proposer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('objective', 300);
            $table->date('due_on');
            $table->string('status', 20)->default('proposee');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });

        // CO-02 : tableau de tâches partagé du projet.
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('a_faire');
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        // MR-07 : rendez-vous en visio ou en présentiel.
        Schema::create('meetings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contact_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('proposer_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->string('mode', 20);
            $table->string('place', 300)->nullable();
            $table->text('agenda')->nullable();
            $table->string('status', 20)->default('proposee');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });

        // CO-03 et FC-06 : collaborations et réussites confirmées par les deux parties.
        Schema::create('collaborations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contact_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('declared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('partner_id')->constrained('users')->cascadeOnDelete();
            $table->string('summary', 300);
            $table->string('status', 20)->default('en_attente');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignUlid('collaboration_id')->nullable()->after('project_update_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collaboration_id');
        });

        Schema::dropIfExists('collaborations');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('agreements');
    }
};
