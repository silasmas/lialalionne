<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table de journalisation du consentement cookies (preuve RGPD).
   */
  public function up(): void
  {
    Schema::create('cookie_consents', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->string('session_id')->nullable();
      $table->string('ip_address', 45)->nullable();
      $table->string('user_agent')->nullable();
      $table->boolean('analytics')->default(false);
      $table->timestamp('consented_at');
      $table->timestamps();

      $table->index(['session_id']);
    });
  }

  /**
   * Supprime la table de journalisation du consentement cookies.
   */
  public function down(): void
  {
    Schema::dropIfExists('cookie_consents');
  }
};
