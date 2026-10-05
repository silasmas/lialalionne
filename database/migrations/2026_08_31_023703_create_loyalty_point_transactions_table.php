<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée l'historique des mouvements de points de fidélité.
   */
  public function up(): void
  {
    Schema::create('loyalty_point_transactions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
      $table->integer('points');
      $table->string('type');
      $table->string('description')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime l'historique des mouvements de points de fidélité.
   */
  public function down(): void
  {
    Schema::dropIfExists('loyalty_point_transactions');
  }
};
