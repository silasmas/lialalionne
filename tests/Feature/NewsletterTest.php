<?php

namespace Tests\Feature;

use App\Livewire\Shop\NewsletterForm;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests du formulaire d'inscription newsletter.
 */
class NewsletterTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie qu'une adresse valide crée un inscrit.
   *
   * @return void
   */
  public function testValidEmailSubscribes(): void
  {
    Livewire::test(NewsletterForm::class)
      ->set('email', 'client@example.com')
      ->call('subscribe')
      ->assertSet('subscribed', true);

    $this->assertDatabaseHas('newsletter_subscribers', [
      'email' => 'client@example.com',
    ]);
  }

  /**
   * Vérifie qu'une adresse invalide est rejetée.
   *
   * @return void
   */
  public function testInvalidEmailIsRejected(): void
  {
    Livewire::test(NewsletterForm::class)
      ->set('email', 'pas-un-email')
      ->call('subscribe')
      ->assertHasErrors('email');

    $this->assertDatabaseCount('newsletter_subscribers', 0);
  }

  /**
   * Vérifie qu'une re-inscription réactive un désabonnement existant sans doublon.
   *
   * @return void
   */
  public function testResubscribeReactivatesExistingRecord(): void
  {
    NewsletterSubscriber::query()->create([
      'email' => 'ancien@example.com',
      'subscribed_at' => now()->subMonth(),
      'unsubscribed_at' => now()->subDay(),
    ]);

    Livewire::test(NewsletterForm::class)
      ->set('email', 'ancien@example.com')
      ->call('subscribe');

    $this->assertDatabaseCount('newsletter_subscribers', 1);
    $this->assertNull(NewsletterSubscriber::query()->first()->unsubscribed_at);
  }
}
