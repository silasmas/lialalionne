<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute l'adresse de livraison enregistrée dans le profil client.
   */
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('delivery_address_line_1')->nullable()->after('phone');
      $table->string('delivery_address_line_2')->nullable()->after('delivery_address_line_1');
      $table->string('delivery_city')->nullable()->after('delivery_address_line_2');
      $table->string('delivery_postal_code', 20)->nullable()->after('delivery_city');
      $table->string('delivery_country', 2)->default('CD')->after('delivery_postal_code');
    });
  }

  /**
   * Supprime les champs d'adresse de livraison du profil client.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn([
        'delivery_address_line_1',
        'delivery_address_line_2',
        'delivery_city',
        'delivery_postal_code',
        'delivery_country',
      ]);
    });
  }
};
