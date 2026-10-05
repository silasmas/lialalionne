<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protège l'API du bot WhatsApp par une clé partagée (en-tête X-Bot-Key
 * ou paramètre ?bot_token= dans l'URL du webhook Callbell).
 */
class AuthenticateBot
{
  /**
   * Refuse la requête si la clé est absente, invalide ou non configurée.
   *
   * @param Request $request Requête HTTP
   * @param Closure $next Suite du pipeline
   * @return Response Réponse HTTP
   */
  public function handle(Request $request, Closure $next): Response
  {
    $expected = (string) config('bot.api_key');

    if ($expected === '') {
      return response()->json([
        'message' => 'API bot désactivée : BOT_API_KEY non configurée.',
      ], 503);
    }

    // En-tête X-Bot-Key, ou ?bot_token= (webhook Callbell, comme pour Rachoux).
    $given = (string) ($request->header('X-Bot-Key') ?? $request->query('bot_token', ''));

    if ($given === '' || !hash_equals($expected, $given)) {
      return response()->json(['message' => 'Clé bot invalide.'], 401);
    }

    return $next($request);
  }
}
