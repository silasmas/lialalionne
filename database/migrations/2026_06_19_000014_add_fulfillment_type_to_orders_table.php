<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute le mode de livraison (domicile ou retrait boutique).
   */
  public function up(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->string('fulfillment_type', 20)->default('delivery')->after('payment_method');
    });
  }

  /**
   * Supprime la colonne fulfillment_type.
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->dropColumn('fulfillment_type');
    });
  }
};
