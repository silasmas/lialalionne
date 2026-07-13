<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Service de génération, envoi et validation des codes OTP client.
 */
class OtpService
{
  private const CODE_LENGTH = 6;

  private const TTL_MINUTES = 10;

  private const MAX_ATTEMPTS_PER_HOUR = 5;

  /**
   * @param KeccelSmsService $keccelSmsService Passerelle SMS Keccel
   */
  public function __construct(
    private readonly KeccelSmsService $keccelSmsService
  ) {
  }

  /**
   * Crée et envoie un OTP pour connexion ou inscription.
   *
   * @param string $identifier Email ou téléphone du client
   * @param string $channel Canal email ou sms
   * @param string $purpose Contexte login ou register
   * @param array<string, mixed> $metadata Données additionnelles (nom, etc.)
   * @return void
   */
  public function send(string $identifier, string $channel, string $purpose, array $metadata = []): void
  {
    $this->assertRateLimit($identifier, $purpose);

    OtpCode::query()
      ->where('identifier', $identifier)
      ->where('purpose', $purpose)
      ->whereNull('verified_at')
      ->delete();

    $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

    OtpCode::query()->create([
      'identifier' => $identifier,
      'channel' => $channel,
      'purpose' => $purpose,
      'code' => $code,
      'expires_at' => now()->addMinutes(self::TTL_MINUTES),
      'metadata' => $metadata,
    ]);

    if ($channel === 'email') {
      $label = $purpose === 'register' ? 'inscription' : 'connexion';
      Mail::to($identifier)->send(new OtpMail($code, $label));

      return;
    }

    $message = "Votre code Lialalionne : {$code}. Valide 10 minutes. Ne le partagez pas.";

    if (!$this->keccelSmsService->isConfigured()) {
      Log::info('OTP SMS simulé (Keccel non configuré)', [
        'phone' => $identifier,
        'code' => $code,
        'purpose' => $purpose,
      ]);

      return;
    }

    $this->keccelSmsService->send($identifier, $message);
  }

  /**
   * Vérifie un code OTP saisi par le client.
   *
   * @param string $identifier Email ou téléphone
   * @param string $purpose Contexte login ou register
   * @param string $code Code saisi
   * @return OtpCode Enregistrement OTP validé
   */
  public function verify(string $identifier, string $purpose, string $code): OtpCode
  {
    $otp = OtpCode::query()
      ->where('identifier', $identifier)
      ->where('purpose', $purpose)
      ->whereNull('verified_at')
      ->latest()
      ->first();

    if (!$otp || !$otp->isValid()) {
      throw ValidationException::withMessages([
        'otp' => 'Code expiré ou invalide. Demandez un nouveau code.',
      ]);
    }

    $otp->increment('attempts');

    if ($otp->code !== $code) {
      throw ValidationException::withMessages([
        'otp' => 'Code incorrect.',
      ]);
    }

    $otp->update(['verified_at' => now()]);

    return $otp->fresh();
  }

  /**
   * Limite le nombre d'envois OTP par heure et par identifiant.
   *
   * @param string $identifier Email ou téléphone
   * @param string $purpose Contexte OTP
   * @return void
   */
  private function assertRateLimit(string $identifier, string $purpose): void
  {
    $count = OtpCode::query()
      ->where('identifier', $identifier)
      ->where('purpose', $purpose)
      ->where('created_at', '>=', now()->subHour())
      ->count();

    if ($count >= self::MAX_ATTEMPTS_PER_HOUR) {
      throw ValidationException::withMessages([
        'otp' => 'Trop de tentatives. Réessayez dans une heure.',
      ]);
    }
  }
}
