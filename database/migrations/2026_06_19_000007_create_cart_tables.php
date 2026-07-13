<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée les tables panier et articles du panier.
   */
  public function up(): void
  {
    Schema::create('carts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->string('session_id')->nullable()->index();
      $table->timestamps();
    });

    Schema::create('cart_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
      $table->foreignId('product_id')->constrained()->cascadeOnDelete();
      $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
      $table->unsignedInteger('quantity')->default(1);
      $table->decimal('unit_price', 10, 2);
      $table->timestamps();

      $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_item_unique');
    });
  }

  /**
   * Supprime les tables panier.
   */
  public function down(): void
  {
    Schema::dropIfExists('cart_items');
    Schema::dropIfExists('carts');
  }
};
