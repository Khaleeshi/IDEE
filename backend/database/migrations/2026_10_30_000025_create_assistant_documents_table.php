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
        // IA-05 : préparation d'un rendez-vous ; IA-06 : présentation du projet (pitch deck).
        Schema::create('assistant_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('kind', 30);
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('meeting_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 20);
            $table->json('content')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->index(['kind', 'project_id']);
            $table->index(['kind', 'meeting_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_documents');
    }
};
