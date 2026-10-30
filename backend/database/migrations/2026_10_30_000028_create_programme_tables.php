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
        // OR-02 : un programme d'organisation vérifiée peut recevoir des candidatures depuis IDEE.
        Schema::table('opportunities', function (Blueprint $table) {
            $table->boolean('accepts_applications')->default(false)->after('is_published');
            $table->json('application_questions')->nullable()->after('accepts_applications');
        });

        Schema::create('programme_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->json('business_plan')->nullable();
            $table->string('status', 20);
            $table->text('feedback')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['opportunity_id', 'project_id']);
        });

        // OR-03 : suivi de cohortes pour les incubateurs et les programmes.
        Schema::create('cohorts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organisation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });

        Schema::create('cohort_projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('cohort_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cohort_id', 'project_id']);
        });

        Schema::create('cohort_checkins', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('cohort_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->text('note');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cohort_checkins');
        Schema::dropIfExists('cohort_projects');
        Schema::dropIfExists('cohorts');
        Schema::dropIfExists('programme_applications');

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['accepts_applications', 'application_questions']);
        });
    }
};
