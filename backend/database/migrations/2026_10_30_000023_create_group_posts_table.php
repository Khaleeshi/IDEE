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
        // FC-05 : discussions des groupes par secteur ou par région.
        Schema::create('group_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('group_type', 20);
            $table->string('group_key', 100);
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->index(['group_type', 'group_key', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_posts');
    }
};
