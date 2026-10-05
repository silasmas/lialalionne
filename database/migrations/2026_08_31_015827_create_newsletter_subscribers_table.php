<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des inscrits à la newsletter.
   */
  public function up(): void
  {
    Schema::create('newsletter_subscribers', function (Blueprint $table) {
      $table->id();
      $table->string('email')->unique();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->timestamp('subscribed_at');
      $table->timestamp('unsubscribed_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des inscrits à la newsletter.
   */
  public function down(): void
  {
    Schema::dropIfExists('newsletter_subscribers');
  }
};
