<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paiements liés aux commandes.
   */
  public function up(): void
  {
    Schema::create('payments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('order_id')->constrained()->cascadeOnDelete();
      $table->string('method');
      $table->string('status')->default('pending');
      $table->decimal('amount', 10, 2);
      $table->string('currency', 3)->default('EUR');
      $table->string('transaction_id')->nullable()->index();
      $table->json('metadata')->nullable();
      $table->timestamp('paid_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des paiements.
   */
  public function down(): void
  {
    Schema::dropIfExists('payments');
  }
};
