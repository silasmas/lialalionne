<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des adresses de livraison/facturation.
   */
  public function up(): void
  {
    Schema::create('order_addresses', function (Blueprint $table) {
      $table->id();
      $table->foreignId('order_id')->constrained()->cascadeOnDelete();
      $table->string('type')->default('shipping');
      $table->string('first_name');
      $table->string('last_name');
      $table->string('phone', 20)->nullable();
      $table->string('address_line_1');
      $table->string('address_line_2')->nullable();
      $table->string('city');
      $table->string('state')->nullable();
      $table->string('postal_code', 20);
      $table->string('country', 2)->default('FR');
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des adresses de commande.
   */
  public function down(): void
  {
    Schema::dropIfExists('order_addresses');
  }
};
