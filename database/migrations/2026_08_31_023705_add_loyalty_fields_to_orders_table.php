<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute le suivi des points de fidélité utilisés/gagnés sur une commande.
   */
  public function up(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->unsignedInteger('loyalty_points_redeemed')->default(0)->after('discount_amount');
      $table->unsignedInteger('loyalty_points_earned')->default(0)->after('loyalty_points_redeemed');
    });
  }

  /**
   * Retire le suivi des points de fidélité de la commande.
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->dropColumn(['loyalty_points_redeemed', 'loyalty_points_earned']);
    });
  }
};
