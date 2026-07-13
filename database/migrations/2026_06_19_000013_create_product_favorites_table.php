<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table pivot favoris client / produit.
   */
  public function up(): void
  {
    Schema::create('product_favorites', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('product_id')->constrained()->cascadeOnDelete();
      $table->timestamps();

      $table->unique(['user_id', 'product_id']);
    });
  }

  /**
   * Supprime la table favoris.
   */
  public function down(): void
  {
    Schema::dropIfExists('product_favorites');
  }
};
