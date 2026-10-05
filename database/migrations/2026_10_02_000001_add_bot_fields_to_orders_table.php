<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute l'origine de la commande (site / WhatsApp) et le lien de paiement
 * unique envoyé par le bot.
 */
return new class extends Migration
{
  /**
   * @return void
   */
  public function up(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->string('source', 20)->default('web')->after('currency');
      $table->string('payment_token', 64)->nullable()->unique()->after('source');
      $table->timestamp('payment_token_expires_at')->nullable()->after('payment_token');
    });
  }

  /**
   * @return void
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->dropUnique(['payment_token']);
      $table->dropColumn(['source', 'payment_token', 'payment_token_expires_at']);
    });
  }
};
