<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paramètres applicatifs (clé/valeur JSON).
   */
  public function up(): void
  {
    Schema::create('settings', function (Blueprint $table) {
      $table->id();
      $table->string('key')->unique();
      $table->json('value')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des paramètres.
   */
  public function down(): void
  {
    Schema::dropIfExists('settings');
  }
};
