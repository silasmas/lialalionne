<?php

namespace Tests\Unit;

use App\Enums\MobileMoneyOperator;
use App\Services\MobileMoneyService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MobileMoneyServiceTest extends TestCase
{
  private MobileMoneyService $service;

  protected function setUp(): void
  {
    parent::setUp();
    $this->service = new MobileMoneyService();
  }

  public function testAcceptsInternationalMpesaNumber(): void
  {
    $normalized = $this->service->validatePhoneForOperator('243827839232', MobileMoneyOperator::Mpesa);

    $this->assertSame('243827839232', $normalized);
  }

  public function testAcceptsLocalMpesaNumber(): void
  {
    $normalized = $this->service->validatePhoneForOperator('0827839232', MobileMoneyOperator::Mpesa);

    $this->assertSame('243827839232', $normalized);
  }

  public function testRejectsWrongOperatorPrefix(): void
  {
    $this->expectException(ValidationException::class);

    $this->service->validatePhoneForOperator('243997123456', MobileMoneyOperator::Mpesa);
  }

  public function testAcceptsInternationalAirtelNumber(): void
  {
    $normalized = $this->service->validatePhoneForOperator('243991234567', MobileMoneyOperator::Airtel);

    $this->assertSame('243991234567', $normalized);
  }
}
