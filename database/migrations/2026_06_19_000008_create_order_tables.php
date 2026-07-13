<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée les tables commandes et lignes de commande.
   */
  public function up(): void
  {
    Schema::create('orders', function (Blueprint $table) {
      $table->id();
      $table->string('order_number')->unique();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->string('status')->default('pending');
      $table->string('payment_method')->nullable();
      $table->decimal('subtotal', 10, 2);
      $table->decimal('shipping_amount', 10, 2)->default(0);
      $table->decimal('discount_amount', 10, 2)->default(0);
      $table->decimal('tax_amount', 10, 2)->default(0);
      $table->decimal('total', 10, 2);
      $table->string('currency', 3)->default('EUR');
      $table->text('notes')->nullable();
      $table->string('tracking_number')->nullable();
      $table->timestamp('shipped_at')->nullable();
      $table->timestamp('delivered_at')->nullable();
      $table->timestamps();
    });

    Schema::create('order_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('order_id')->constrained()->cascadeOnDelete();
      $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
      $table->string('product_name');
      $table->string('variant_name')->nullable();
      $table->string('sku');
      $table->unsignedInteger('quantity');
      $table->decimal('unit_price', 10, 2);
      $table->decimal('total_price', 10, 2);
      $table->timestamps();
    });
  }

  /**
   * Supprime les tables commandes.
   */
  public function down(): void
  {
    Schema::dropIfExists('order_items');
    Schema::dropIfExists('orders');
  }
};
