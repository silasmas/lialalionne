<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Tests du service de codes promotionnels.
 */
class CouponServiceTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Crée un coupon de test.
   *
   * @param array<string, mixed> $overrides Attributs à surcharger
   * @return Coupon Coupon créé
   */
  private function makeCoupon(array $overrides = []): Coupon
  {
    return Coupon::query()->create(array_merge([
      'code' => 'TEST10',
      'name' => 'Test 10%',
      'type' => CouponType::Percent,
      'value' => 10,
      'is_active' => true,
      'times_used' => 0,
    ], $overrides));
  }

  /**
   * Vérifie le calcul d'une remise en pourcentage.
   *
   * @return void
   */
  public function test_calculates_percent_discount(): void
  {
    $coupon = $this->makeCoupon(['value' => 10]);
    $service = app(CouponService::class);

    $this->assertSame(5.0, $service->calculateDiscountEur($coupon, 50));
  }

  /**
   * Vérifie le plafond de remise sur un pourcentage.
   *
   * @return void
   */
  public function test_respects_max_discount_cap(): void
  {
    $coupon = $this->makeCoupon([
      'value' => 50,
      'max_discount_amount' => 8,
    ]);
    $service = app(CouponService::class);

    $this->assertSame(8.0, $service->calculateDiscountEur($coupon, 100));
  }

  /**
   * Vérifie qu'un code invalide est rejeté.
   *
   * @return void
   */
  public function test_rejects_unknown_code(): void
  {
    $this->expectException(ValidationException::class);

    app(CouponService::class)->validateForCheckout('INEXISTANT', 40);
  }

  /**
   * Vérifie qu'un montant minimum est appliqué.
   *
   * @return void
   */
  public function test_rejects_below_minimum_order(): void
  {
    $this->makeCoupon(['min_order_amount' => 30]);

    $this->expectException(ValidationException::class);

    app(CouponService::class)->validateForCheckout('TEST10', 20);
  }

  /**
   * Vérifie la limite par utilisateur connecté.
   *
   * @return void
   */
  public function test_requires_login_when_per_user_limit_set(): void
  {
    $this->makeCoupon(['max_uses_per_user' => 1]);

    $this->expectException(ValidationException::class);

    app(CouponService::class)->validateForCheckout('TEST10', 40, null);
  }

  /**
   * Vérifie qu'un code valide est accepté pour un client connecté.
   *
   * @return void
   */
  public function test_accepts_valid_code_for_user(): void
  {
    $this->makeCoupon(['code' => 'OK20', 'type' => CouponType::Fixed, 'value' => 5]);
    $user = User::factory()->create();

    $coupon = app(CouponService::class)->validateForCheckout('ok20', 40, $user);

    $this->assertSame('OK20', $coupon->code);
  }
}
