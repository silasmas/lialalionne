<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie que la page d'accueil répond sans erreur.
   */
  public function test_the_application_returns_a_successful_response(): void
  {
    $response = $this->get('/');

    $response->assertStatus(200);
  }
}
