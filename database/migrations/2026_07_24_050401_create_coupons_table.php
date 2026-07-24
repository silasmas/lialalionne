<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des codes promotionnels partageables.
 */
return new class extends Migration
{
  /**
   * Exécute la migration.
   *
   * @return void
   */
  public function up(): void
  {
    Schema::create('coupons', function (Blueprint $table) {
      $table->id();
      $table->string('code')->unique();
      $table->string('name');
      $table->string('type', 20);
      $table->decimal('value', 10, 2);
      $table->decimal('min_order_amount', 10, 2)->nullable();
      $table->decimal('max_discount_amount', 10, 2)->nullable();
      $table->unsignedInteger('max_uses')->nullable();
      $table->unsignedInteger('max_uses_per_user')->nullable();
      $table->unsignedInteger('times_used')->default(0);
      $table->timestamp('starts_at')->nullable();
      $table->timestamp('ends_at')->nullable();
      $table->boolean('is_active')->default(true);
      $table->text('description')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Annule la migration.
   *
   * @return void
   */
  public function down(): void
  {
    Schema::dropIfExists('coupons');
  }
};
