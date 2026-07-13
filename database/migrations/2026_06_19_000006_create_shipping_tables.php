<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée les tables de zones et tarifs de livraison.
   */
  public function up(): void
  {
    Schema::create('shipping_zones', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->json('countries')->nullable();
      $table->json('regions')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });

    Schema::create('shipping_rates', function (Blueprint $table) {
      $table->id();
      $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
      $table->string('name');
      $table->decimal('min_order_amount', 10, 2)->default(0);
      $table->decimal('max_order_amount', 10, 2)->nullable();
      $table->decimal('price', 10, 2);
      $table->unsignedInteger('estimated_days_min')->nullable();
      $table->unsignedInteger('estimated_days_max')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Supprime les tables de livraison.
   */
  public function down(): void
  {
    Schema::dropIfExists('shipping_rates');
    Schema::dropIfExists('shipping_zones');
  }
};
