<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des codes OTP temporaires (connexion / inscription).
   */
  public function up(): void
  {
    Schema::create('otp_codes', function (Blueprint $table) {
      $table->id();
      $table->string('identifier');
      $table->string('channel', 20);
      $table->string('purpose', 30);
      $table->string('code', 10);
      $table->unsignedTinyInteger('attempts')->default(0);
      $table->timestamp('expires_at');
      $table->timestamp('verified_at')->nullable();
      $table->json('metadata')->nullable();
      $table->timestamps();

      $table->index(['identifier', 'purpose', 'expires_at']);
    });
  }

  /**
   * Supprime la table OTP.
   */
  public function down(): void
  {
    Schema::dropIfExists('otp_codes');
  }
};
