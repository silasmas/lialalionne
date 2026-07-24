<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lie une commande à un code promo (historique conservé via coupon_code).
 */
return new class extends Migration
{
  /**
   * Exécute la migration.
   *
   * @return void
   */
  public function up(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->foreignId('coupon_id')
        ->nullable()
        ->after('notes')
        ->constrained('coupons')
        ->nullOnDelete();
      $table->string('coupon_code')
        ->nullable()
        ->after('coupon_id');
    });
  }

  /**
   * Annule la migration.
   *
   * @return void
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->dropConstrainedForeignId('coupon_id');
      $table->dropColumn('coupon_code');
    });
  }
};
