<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des slides d'accueil (photo ou vidéo).
   */
  public function up(): void
  {
    Schema::create('home_slides', function (Blueprint $table) {
      $table->id();
      $table->string('media_type')->default('image');
      $table->string('image_path')->nullable();
      $table->string('video_path')->nullable();
      $table->string('poster_path')->nullable();
      $table->string('kicker')->nullable();
      $table->string('title');
      $table->string('button_label')->nullable();
      $table->string('button_url')->nullable();
      $table->unsignedInteger('sort_order')->default(0);
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des slides d'accueil.
   */
  public function down(): void
  {
    Schema::dropIfExists('home_slides');
  }
};
