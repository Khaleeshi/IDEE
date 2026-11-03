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
        // PA-04 : prestataire qui traite le paiement (wave, bictorys) et consignes à afficher
        // (code USSD d'Orange Money, par exemple).
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('method');
            $table->text('instructions')->nullable()->after('launch_url');
        });

        // CP-07 : SMS de connexion payé d'avance, avant même que le compte existe.
        Schema::create('sms_purchases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('phone', 20)->index();
            $table->unsignedInteger('amount_fcfa');
            $table->string('method', 30);
            $table->string('status', 20);
            $table->string('provider_reference')->nullable()->index();
            $table->string('launch_url', 1000)->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedTinyInteger('sends_remaining');
            $table->string('receipt_number', 30)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_purchases');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['provider', 'instructions']);
        });
    }
};
