<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversations WhatsApp gérées par l'IA (mémoire courte + journal complet
 * relu chaque semaine pour améliorer les consignes).
 */
return new class extends Migration
{
  /**
   * @return void
   */
  public function up(): void
  {
    Schema::create('bot_conversations', function (Blueprint $table) {
      $table->id();
      $table->string('phone', 20)->unique();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->string('status', 20)->default('bot');
      $table->json('history')->nullable();
      $table->json('meta')->nullable();
      $table->string('handoff_reason')->nullable();
      $table->timestamp('handed_off_at')->nullable();
      $table->timestamp('last_message_at')->nullable();
      $table->timestamps();
    });

    Schema::create('bot_messages', function (Blueprint $table) {
      $table->id();
      $table->foreignId('bot_conversation_id')->constrained()->cascadeOnDelete();
      $table->string('role', 20);
      $table->text('content')->nullable();
      $table->json('meta')->nullable();
      $table->timestamps();

      $table->index(['bot_conversation_id', 'created_at']);
    });
  }

  /**
   * @return void
   */
  public function down(): void
  {
    Schema::dropIfExists('bot_messages');
    Schema::dropIfExists('bot_conversations');
  }
};
