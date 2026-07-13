<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute les champs client au modèle utilisateur.
   */
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('phone', 20)->nullable()->after('email');
      $table->boolean('is_admin')->default(false)->after('password');
    });
  }

  /**
   * Supprime les champs client du modèle utilisateur.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['phone', 'is_admin']);
    });
  }
};
