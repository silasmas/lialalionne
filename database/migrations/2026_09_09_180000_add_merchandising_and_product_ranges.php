<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les drapeaux merchandising (nouveautés / saison) et les liens de gamme.
 */
return new class extends Migration
{
  /**
   * Crée les colonnes merchandising et la table pivot des produits liés.
   *
   * @return void
   */
  public function up(): void
  {
    Schema::table('products', function (Blueprint $table): void {
      $table->boolean('is_new')->default(false)->after('is_featured');
      $table->boolean('is_seasonal')->default(false)->after('is_new');
    });

    Schema::create('product_related', function (Blueprint $table): void {
      $table->id();
      $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
      $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
      $table->timestamps();
      $table->unique(['product_id', 'related_product_id']);
    });

    $this->backfillMerchandisingFlags();
  }

  /**
   * Marque les produits du catalogue réel pour les blocs accueil / boutique.
   *
   * @return void
   */
  private function backfillMerchandisingFlags(): void
  {
    if (!Schema::hasTable('products')) {
      return;
    }

    Product::query()
      ->whereIn('sku', ['CL-MIN-0004', 'CL-HUI-0005', 'CL-MIN-0006', 'DZ-MIN-0011', 'YK-MIN-0013'])
      ->update(['is_new' => true]);

    Product::query()
      ->whereIn('sku', ['CL-FES-0002', 'CL-COR-0007', 'CL-COR-0008', 'CL-GOM-0009', 'DZ-FES-0012'])
      ->update(['is_seasonal' => true]);
  }

  /**
   * Annule les colonnes merchandising et la table des gammes.
   *
   * @return void
   */
  public function down(): void
  {
    Schema::dropIfExists('product_related');

    Schema::table('products', function (Blueprint $table): void {
      $table->dropColumn(['is_new', 'is_seasonal']);
    });
  }
};
