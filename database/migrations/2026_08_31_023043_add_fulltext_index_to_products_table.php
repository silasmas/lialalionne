<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute un index FULLTEXT (nom + description courte) pour la recherche.
   * MySQL uniquement : SQLite (tests) et les autres pilotes ne le supportent
   * pas — la recherche y reste alors un LIKE simple, suffisant en test.
   */
  public function up(): void
  {
    if (Schema::getConnection()->getDriverName() !== 'mysql') {
      return;
    }

    Schema::table('products', function (Blueprint $table) {
      $table->fullText(['name', 'short_description']);
    });
  }

  /**
   * Supprime l'index FULLTEXT.
   */
  public function down(): void
  {
    if (Schema::getConnection()->getDriverName() !== 'mysql') {
      return;
    }

    Schema::table('products', function (Blueprint $table) {
      $table->dropFullText(['name', 'short_description']);
    });
  }
};
