<?php

namespace Tests\Feature;

use App\Enums\AuthMode;
use App\Livewire\Account\LoginPage;
use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests de l'authentification OTP (service + page Livewire).
 */
class OtpAuthTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie qu'un OTP email est créé et envoyé.
   *
   * @return void
   */
  public function testOtpServiceSendsEmailCode(): void
  {
    Mail::fake();

    app(OtpService::class)->send('marie@example.com', 'email', 'login');

    $otp = OtpCode::query()->where('identifier', 'marie@example.com')->first();

    $this->assertNotNull($otp);
    $this->assertSame(6, strlen((string) $otp->code));
    $this->assertNull($otp->verified_at);

    Mail::assertSent(OtpMail::class, function (OtpMail $mail) {
      return $mail->hasTo('marie@example.com');
    });
  }

  /**
   * Vérifie qu'un code correct est accepté.
   *
   * @return void
   */
  public function testOtpServiceVerifiesValidCode(): void
  {
    Mail::fake();

    app(OtpService::class)->send('marie@example.com', 'email', 'login');

    $code = OtpCode::query()->where('identifier', 'marie@example.com')->value('code');
    $verified = app(OtpService::class)->verify('marie@example.com', 'login', $code);

    $this->assertNotNull($verified->verified_at);
  }

  /**
   * Vérifie qu'un code incorrect est rejeté.
   *
   * @return void
   */
  public function testOtpServiceRejectsInvalidCode(): void
  {
    Mail::fake();

    app(OtpService::class)->send('marie@example.com', 'email', 'login');

    $this->expectException(ValidationException::class);

    app(OtpService::class)->verify('marie@example.com', 'login', '000000');
  }

  /**
   * Vérifie le verrouillage après 5 tentatives incorrectes.
   *
   * @return void
   */
  public function testOtpServiceLocksAfterTooManyFailedAttempts(): void
  {
    Mail::fake();

    app(OtpService::class)->send('marie@example.com', 'email', 'login');

    $otp = OtpCode::query()->where('identifier', 'marie@example.com')->first();
    $correctCode = $otp->code;
    $wrongCode = $correctCode === '000000' ? '111111' : '000000';

    for ($i = 0; $i < 5; $i++) {
      try {
        app(OtpService::class)->verify('marie@example.com', 'login', $wrongCode);
      } catch (ValidationException) {
        //
      }
    }

    $this->expectException(ValidationException::class);

    app(OtpService::class)->verify('marie@example.com', 'login', $correctCode);
  }

  /**
   * Vérifie la limite d'envois OTP par heure.
   *
   * @return void
   */
  public function testOtpServiceRateLimitsSends(): void
  {
    Mail::fake();

    $service = app(OtpService::class);

    for ($i = 0; $i < 5; $i++) {
      $service->send('marie@example.com', 'email', 'login');
    }

    $this->expectException(ValidationException::class);

    $service->send('marie@example.com', 'email', 'login');
  }

  /**
   * Vérifie le parcours Livewire : envoi OTP puis connexion.
   *
   * @return void
   */
  public function testLivewireLoginWithEmailOtp(): void
  {
    Mail::fake();

    app(SiteSettingsService::class)->setMany([
      'auth_mode' => AuthMode::EmailOtp->value,
    ]);

    $user = User::factory()->create([
      'email' => 'marie.otp@example.com',
      'is_admin' => false,
    ]);

    $component = Livewire::test(LoginPage::class)
      ->set('email', 'marie.otp@example.com')
      ->call('sendOtp')
      ->assertHasNoErrors()
      ->assertSet('otpSent', true)
      ->assertSet('step', 'otp');

    $code = OtpCode::query()
      ->where('identifier', 'marie.otp@example.com')
      ->where('purpose', 'login')
      ->value('code');

    $this->assertNotNull($code);

    $component
      ->set('otpCode', $code)
      ->call('verifyOtpAndLogin')
      ->assertHasNoErrors()
      ->assertRedirect(route('account.dashboard'));

    $this->assertAuthenticatedAs($user);
  }

  /**
   * Vérifie qu'un OTP Livewire incorrect affiche une erreur.
   *
   * @return void
   */
  public function testLivewireLoginRejectsWrongOtp(): void
  {
    Mail::fake();

    app(SiteSettingsService::class)->setMany([
      'auth_mode' => AuthMode::EmailOtp->value,
    ]);

    User::factory()->create([
      'email' => 'marie.otp@example.com',
      'is_admin' => false,
    ]);

    Livewire::test(LoginPage::class)
      ->set('email', 'marie.otp@example.com')
      ->call('sendOtp')
      ->set('otpCode', '000000')
      ->call('verifyOtpAndLogin')
      ->assertHasErrors(['otpCode']);

    $this->assertGuest();
  }
}
