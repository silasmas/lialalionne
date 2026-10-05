<?php

namespace App\Http\Controllers;

use App\Models\CookieConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Journalise le consentement cookies côté serveur (preuve RGPD).
 */
class ConsentController extends Controller
{
  /**
   * @param Request $request Requête contenant le choix de consentement
   * @return Response Réponse vide
   */
  public function store(Request $request): Response
  {
    $data = $request->validate([
      'analytics' => ['required', 'boolean'],
    ]);

    CookieConsent::query()->create([
      'user_id' => $request->user()?->id,
      'session_id' => $request->session()->getId(),
      'ip_address' => $request->ip(),
      'user_agent' => (string) $request->userAgent(),
      'analytics' => $data['analytics'],
      'consented_at' => now(),
    ]);

    return response()->noContent();
  }
}
