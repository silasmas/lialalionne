<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute le suivi du dernier numéro de colis notifié par email.
   */
  public function up(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->string('shipment_notified_tracking')->nullable()->after('tracking_number');
    });
  }

  /**
   * Supprime la colonne de suivi des notifications d'expédition.
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->dropColumn('shipment_notified_tracking');
    });
  }
};
