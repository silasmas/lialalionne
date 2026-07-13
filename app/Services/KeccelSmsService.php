<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Envoi de SMS via l'API Keccel (RDC).
 */
class KeccelSmsService
{
  /**
   * Indique si Keccel SMS est configuré (token présent).
   *
   * @return bool True si prêt pour l'envoi
   */
  public function isConfigured(): bool
  {
    return !empty(config('services.keccel.token'));
  }

  /**
   * Envoie un SMS au numéro congolais normalisé.
   *
   * @param string $phone Numéro destinataire
   * @param string $message Corps du message
   * @return void
   */
  public function send(string $phone, string $message): void
  {
    if (!$this->isConfigured()) {
      Log::info('Keccel SMS simulé (token absent)', [
        'phone' => $phone,
        'message' => $message,
      ]);

      return;
    }

    $response = Http::acceptJson()
      ->post(config('services.keccel.gateway'), [
        'to' => $this->normalizePhone($phone),
        'message' => $message,
        'from' => config('services.keccel.sender', 'LIALALIONNE'),
        'token' => config('services.keccel.token'),
      ]);

    $body = $response->json() ?? [];

    if (!$response->successful() || ($body['status'] ?? '') === 'FAILED') {
      Log::error('Keccel SMS failed', ['body' => $body, 'status' => $response->status()]);
      throw ValidationException::withMessages([
        'otp' => $body['message'] ?? 'Impossible d\'envoyer le SMS. Réessayez plus tard.',
      ]);
    }
  }

  /**
   * Normalise un numéro Mobile congolais (243XXXXXXXXX).
   *
   * @param string $phone Numéro saisi
   * @return string Numéro normalisé
   */
  private function normalizePhone(string $phone): string
  {
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if (str_starts_with($digits, '0')) {
      $digits = '243' . substr($digits, 1);
    }

    if (!str_starts_with($digits, '243')) {
      $digits = '243' . $digits;
    }

    return $digits;
  }
}
