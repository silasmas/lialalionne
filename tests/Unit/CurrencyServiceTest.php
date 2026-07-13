<?php

namespace Tests\Unit;

use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du service de conversion et formatage CDF / USD.
 */
class CurrencyServiceTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie la conversion EUR → CDF avec le taux par défaut.
   *
   * @return void
   */
  public function testConvertFromEurToCdfUsesDefaultRate(): void
  {
    $service = app(CurrencyService::class);

    $this->assertSame(28500.0, $service->convertFromEur(10, 'CDF'));
    $this->assertSame('28 500 FC', $service->formatFromEur(10, 'CDF'));
  }

  /**
   * Vérifie la conversion EUR → USD.
   *
   * @return void
   */
  public function testConvertFromEurToUsd(): void
  {
    $service = app(CurrencyService::class);

    $this->assertSame(10.8, $service->convertFromEur(10, 'USD'));
    $this->assertSame('10.80 $', $service->formatFromEur(10, 'USD'));
  }

  /**
   * Vérifie la conversion inverse CDF → EUR.
   *
   * @return void
   */
  public function testConvertToEurFromCdf(): void
  {
    $service = app(CurrencyService::class);

    $this->assertSame(10.0, $service->convertToEur(28500, 'CDF'));
  }
}
